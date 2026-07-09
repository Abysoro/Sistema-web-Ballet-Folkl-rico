<?php
include "conexion.php";

header("Content-Type: application/json; charset=utf-8");

$sql = "SELECT id_tipo_evento, nombre_tipo_evento
        FROM tipos_evento
        WHERE estado = 'Activo'
        ORDER BY nombre_tipo_evento ASC";

$res = $conn->query($sql);

if (!$res) {
    echo json_encode([
        "ok" => false,
        "mensaje" => "Error al obtener tipos: " . $conn->error
    ]);
    exit;
}

$datos = [];

while ($fila = $res->fetch_assoc()) {
    $datos[] = $fila;
}

echo json_encode([
    "ok" => true,
    "datos" => $datos
], JSON_UNESCAPED_UNICODE);

$conn->close();
?>