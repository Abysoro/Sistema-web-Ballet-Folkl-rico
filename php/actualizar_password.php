<?php
include "conexion.php";

$token = $_POST['token'] ?? '';
$password = $_POST['password'] ?? '';
$password2 = $_POST['password2'] ?? '';

if ($token === '' || $password === '' || $password2 === '') {
    die("Completa todos los campos.");
}

if ($password !== $password2) {
    die("Las contraseñas no coinciden.");
}

if (
    strlen($password) < 8 ||
    !preg_match('/[A-Z]/', $password) ||
    !preg_match('/[0-9]/', $password)
) {
    die("La contraseña debe tener al menos 8 caracteres, una mayúscula y un número.");
}

$token_hash = hash('sha256', $token);

$stmt = $conn->prepare("SELECT id_recuperacion, id_usuario, expira_en, usado
                        FROM recuperacion_password
                        WHERE token_hash = ?
                        LIMIT 1");
$stmt->bind_param("s", $token_hash);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    die("El token no es válido.");
}

$fila = $res->fetch_assoc();

if ((int)$fila['usado'] === 1) {
    die("Este enlace ya fue utilizado.");
}

if (strtotime($fila['expira_en']) < time()) {
    die("El enlace ha expirado.");
}

$id_usuario = (int)$fila['id_usuario'];
$nuevo_hash = password_hash($password, PASSWORD_DEFAULT);

$stmtUpdate = $conn->prepare("UPDATE usuario SET password_hash = ? WHERE id_usuario = ?");
$stmtUpdate->bind_param("si", $nuevo_hash, $id_usuario);
$stmtUpdate->execute();

$id_recuperacion = (int)$fila['id_recuperacion'];
$stmtUsado = $conn->prepare("UPDATE recuperacion_password SET usado = 1 WHERE id_recuperacion = ?");
$stmtUsado->bind_param("i", $id_recuperacion);
$stmtUsado->execute();

echo "Contraseña actualizada correctamente. <a href='/sistema_ballet/php/login.php'>Iniciar sesión</a>";

$conn->close();
?>