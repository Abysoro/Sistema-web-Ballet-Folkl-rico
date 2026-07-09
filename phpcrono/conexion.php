<?php
$host = "127.0.0.1";
$usuario = "root";
$contrasena = "";
$basedatos = "ballet";
$puerto = 3307;

$conn = new mysqli($host, $usuario, $contrasena, $basedatos, $puerto);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$conn->set_charset("utf8");
?>