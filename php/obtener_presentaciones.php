<?php
include "conexion.php";

header("Content-Type: application/json; charset=utf-8");

$conn->query("
    UPDATE presentaciones
    SET estado = 'Inactiva'
    WHERE fecha < CURDATE()
      AND estado = 'Activa'
");

$sql = "
SELECT 
    p.id_presentacion,
    p.id_jornada,
    p.nombre_presentacion,
    p.id_tipo_evento,
    p.otro_tipo_evento,
    te.nombre_tipo_evento,
    p.id_categoria AS categoria,
    c.nombre_categoria,
    p.capacidad,
    p.fecha,
    p.hora_presentacion,
    p.lugar,
    p.duracion_minutos,
    COALESCE(rp.nivel_minimo, 1) AS nivel_minimo
FROM presentaciones p
INNER JOIN categorias c 
    ON p.id_categoria = c.id_categoria
LEFT JOIN requisitos_presentacion rp
    ON p.id_presentacion = rp.id_presentacion
LEFT JOIN tipos_evento te
    ON p.id_tipo_evento = te.id_tipo_evento
WHERE p.estado = 'Activa'
  AND p.fecha >= CURDATE()
ORDER BY p.fecha ASC, p.hora_presentacion ASC, p.id_presentacion DESC
";

$resultado = $conn->query($sql);

$datos = [];

if (!$resultado) {
    echo json_encode([
        "ok" => false,
        "mensaje" => "Error SQL: " . $conn->error
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

while ($fila = $resultado->fetch_assoc()) {
    $datos[] = $fila;
}

echo json_encode([
    "ok" => true,
    "datos" => $datos
], JSON_UNESCAPED_UNICODE);

$conn->close();
?>