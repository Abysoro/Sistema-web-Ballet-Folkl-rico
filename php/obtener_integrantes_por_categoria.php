<?php
include "conexion.php";

header("Content-Type: application/json; charset=utf-8");

$sql = "
SELECT 
    c.nombre_categoria,
    COUNT(i.id_integrante) AS total
FROM categorias c
LEFT JOIN integrantes i 
    ON i.id_categoria = c.id_categoria
    AND i.estado = 'activo'
GROUP BY c.id_categoria, c.nombre_categoria
ORDER BY c.id_categoria ASC
";

$res = $conn->query($sql);

if (!$res) {
    echo json_encode([
        "ok" => false,
        "mensaje" => "Error al obtener integrantes por categoría: " . $conn->error
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$datos = [];

while ($row = $res->fetch_assoc()) {
    $datos[] = [
        "categoria" => $row["nombre_categoria"],
        "total" => (int)$row["total"]
    ];
}

echo json_encode([
    "ok" => true,
    "categorias" => $datos
], JSON_UNESCAPED_UNICODE);

$conn->close();
?>