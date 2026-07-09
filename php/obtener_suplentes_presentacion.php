<?php
include "conexion.php";

header("Content-Type: application/json; charset=utf-8");

$id_presentacion = isset($_GET['id_presentacion']) ? (int)$_GET['id_presentacion'] : 0;

if ($id_presentacion <= 0) {
    echo json_encode([
        "ok" => false,
        "mensaje" => "ID de presentación inválido."
    ]);
    exit;
}

$sql = "
SELECT
    sp.id_suplente,
    sp.id_presentacion,
    sp.id_integrante,
    sp.orden_suplente,
    sp.tipo_suplencia,
    sp.motivo_suplencia,
    sp.estado,
    i.nombre,
    i.apellido,
    c.nombre_categoria,
    n.nombre_nivel,
    p.nombre_presentacion
FROM suplentes_presentacion sp
INNER JOIN integrantes i
    ON sp.id_integrante = i.id_integrante
INNER JOIN categorias c
    ON sp.categoria_origen = c.id_categoria
INNER JOIN niveles n
    ON sp.nivel_origen = n.id_nivel
INNER JOIN presentaciones p
    ON sp.id_presentacion = p.id_presentacion
WHERE sp.id_presentacion = $id_presentacion
ORDER BY sp.orden_suplente ASC
";

$res = $conn->query($sql);

if (!$res) {
    echo json_encode([
        "ok" => false,
        "mensaje" => "Error al obtener suplentes: " . $conn->error
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