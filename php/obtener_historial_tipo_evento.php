<?php
include "conexion.php";

header("Content-Type: application/json; charset=utf-8");

$id_tipo_evento = isset($_GET['id_tipo_evento']) ? (int)$_GET['id_tipo_evento'] : 0;

if ($id_tipo_evento <= 0) {
    echo json_encode([
        "ok" => false,
        "mensaje" => "Tipo de evento inválido."
    ]);
    exit;
}

$sql = "
SELECT
    p.id_presentacion,
    p.nombre_presentacion,
    p.fecha,
    p.lugar,
    p.otro_tipo_evento,
    te.nombre_tipo_evento,
    c.nombre_categoria
FROM presentaciones p
LEFT JOIN tipos_evento te
    ON p.id_tipo_evento = te.id_tipo_evento
INNER JOIN categorias c
    ON p.id_categoria = c.id_categoria
WHERE p.id_tipo_evento = $id_tipo_evento
ORDER BY p.fecha DESC, p.nombre_presentacion ASC
";

$res = $conn->query($sql);

if (!$res) {
    echo json_encode([
        "ok" => false,
        "mensaje" => "Error al obtener historial: " . $conn->error
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