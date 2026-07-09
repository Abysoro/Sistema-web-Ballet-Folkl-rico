<?php
include "conexion.php";

header('Content-Type: application/json; charset=utf-8');

$nombre_jornada = isset($_POST['nombre_jornada']) ? trim($_POST['nombre_jornada']) : '';
$fecha = isset($_POST['fecha']) ? $_POST['fecha'] : '';
$lugar = isset($_POST['lugar']) ? trim($_POST['lugar']) : '';
$hora_inicio = isset($_POST['hora_inicio']) ? $_POST['hora_inicio'] : '';
$hora_fin = isset($_POST['hora_fin']) ? $_POST['hora_fin'] : '';

if ($nombre_jornada === '' || $fecha === '' || $lugar === '' || $hora_inicio === '' || $hora_fin === '') {
    echo json_encode([
        "ok" => false,
        "mensaje" => "Completa correctamente los datos de la jornada."
    ]);
    exit;
}

if ($hora_fin <= $hora_inicio) {
    echo json_encode([
        "ok" => false,
        "mensaje" => "La hora de fin debe ser mayor a la hora de inicio."
    ]);
    exit;
}

$nombre_jornada = $conn->real_escape_string($nombre_jornada);
$lugar = $conn->real_escape_string($lugar);
$hora_inicio = $conn->real_escape_string($hora_inicio);
$hora_fin = $conn->real_escape_string($hora_fin);

$sql = "INSERT INTO jornadas (nombre_jornada, fecha, lugar, hora_inicio, hora_fin, estado)
        VALUES ('$nombre_jornada', '$fecha', '$lugar', '$hora_inicio', '$hora_fin', 'Activo')";

if (!$conn->query($sql)) {
    echo json_encode([
        "ok" => false,
        "mensaje" => "Error al registrar jornada: " . $conn->error
    ]);
    exit;
}

echo json_encode([
    "ok" => true,
    "id_jornada" => $conn->insert_id
]);

$conn->close();
?>