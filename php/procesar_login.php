<?php
session_start();
include "conexion.php";

function volverLoginConError($mensaje) {
    $_SESSION['login_error'] = $mensaje;
    header("Location: /sistema_ballet/php/login.php");
    exit;
}

$correo = trim($_POST['correo'] ?? '');
$password = $_POST['password'] ?? '';

if ($correo === '' || $password === '') {
    volverLoginConError("Completa el correo y la contraseña.");
}

$stmt = $conn->prepare("
    SELECT id_usuario, nombre_completo, correo, password_hash, rol, estado 
    FROM usuario
    WHERE correo = ? 
    LIMIT 1
");

$stmt->bind_param("s", $correo);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    volverLoginConError("Correo o contraseña incorrectos.");
}

$usuario = $res->fetch_assoc();

if ($usuario['estado'] !== 'activo') {
    volverLoginConError("La cuenta no está activa. Comunícate con el administrador.");
}

if (!password_verify($password, $usuario['password_hash'])) {
    volverLoginConError("Correo o contraseña incorrectos.");
}

$_SESSION['id_usuario'] = $usuario['id_usuario'];
$_SESSION['nombre_usuario'] = $usuario['nombre_completo'];
$_SESSION['correo_usuario'] = $usuario['correo'];
$_SESSION['rol_usuario'] = $usuario['rol'];

$stmt2 = $conn->prepare("UPDATE usuario SET ultimo_login = NOW() WHERE id_usuario = ?");
$stmt2->bind_param("i", $usuario['id_usuario']);
$stmt2->execute();

header("Location: /sistema_ballet/index.php");
exit;
?>