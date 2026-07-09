<?php
include "conexion.php";

header("Content-Type: application/json; charset=utf-8");

$sql = "
SELECT
    i.id_integrante,
    i.nombre,
    i.apellido,
    i.id_categoria,
    i.id_nivel,
    i.estado,
    i.fecha_registro,
    i.fecha_baja,
    i.fecha_reingreso,
    i.cantidad_reingresos,
    c.nombre_categoria,
    n.nombre_nivel,
    COALESCE(ci.rapidez_aprendizaje, 3) AS rapidez_aprendizaje,
    COALESCE(ci.anios_antiguedad, 0) AS anios_antiguedad,
    COALESCE(ci.cantidad_presentaciones, 0) AS cantidad_presentaciones,
    COALESCE(ci.puntaje_disciplina, 3) AS disciplina,
    COALESCE(ci.puntaje_responsabilidad, 3) AS responsabilidad
FROM integrantes i
INNER JOIN categorias c
    ON i.id_categoria = c.id_categoria
INNER JOIN niveles n
    ON i.id_nivel = n.id_nivel
LEFT JOIN criterios_integrante ci
    ON i.id_integrante = ci.id_integrante
WHERE i.estado = 'activo'
ORDER BY i.id_integrante DESC
";

$resultado = $conn->query($sql);

if (!$resultado) {
    echo json_encode([
        "ok" => false,
        "mensaje" => "Error SQL: " . $conn->error
    ]);
    exit;
}

$datos = [];

while ($fila = $resultado->fetch_assoc()) {
    $datos[] = $fila;
}

echo json_encode([
    "ok" => true,
    "datos" => $datos
]);

$conn->close();
?>