<?php
include "conexion.php";

$nombre = "Elmer Santos";
$correo = "aquazul.3el@gmail.com";
$password = "aor13101986";
$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO usuario (nombre_completo, correo, password_hash, rol, estado) VALUES (?, ?, ?, 'admin', 'activo')");
$stmt->bind_param("sss", $nombre, $correo, $hash);

if ($stmt->execute()) {
    echo "Usuario creado correctamente";
} else {
    echo "Error: " . $conn->error;
}

$conn->close();
?>