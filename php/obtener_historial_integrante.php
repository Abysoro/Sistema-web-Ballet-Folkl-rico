<?php
include "conexion.php";

header("Content-Type: application/json; charset=utf-8");

$id = isset($_GET['id_integrante']) ? (int)$_GET['id_integrante'] : 0;

if ($id <= 0) {
    echo json_encode([
        "ok" => false,
        "mensaje" => "ID inválido"
    ]);
    exit;
}

/* datos generales */
$sqlIntegrante = "
SELECT
    i.id_integrante,
    i.nombre,
    i.apellido,
    i.estado,
    i.fecha_registro,
    i.fecha_baja,
    i.fecha_reingreso,
    i.cantidad_reingresos,
    c.nombre_categoria,
    n.nombre_nivel,
    COALESCE(ci.anios_antiguedad, 0) AS anios_antiguedad,
    COALESCE(ci.cantidad_presentaciones, 0) AS cantidad_presentaciones
FROM integrantes i
INNER JOIN categorias c ON i.id_categoria = c.id_categoria
INNER JOIN niveles n ON i.id_nivel = n.id_nivel
LEFT JOIN criterios_integrante ci ON i.id_integrante = ci.id_integrante
WHERE i.id_integrante = $id
LIMIT 1
";

$resIntegrante = mysqli_query($conn, $sqlIntegrante);

if (!$resIntegrante || mysqli_num_rows($resIntegrante) === 0) {
    echo json_encode([
        "ok" => false,
        "mensaje" => "Integrante no encontrado"
    ]);
    exit;
}

$integrante = mysqli_fetch_assoc($resIntegrante);

/* total de asignaciones históricas */
$sqlTotal = "
SELECT COUNT(*) AS total_asignadas
FROM detalle_asignacion
WHERE id_integrante = $id
  AND estado_asignacion = 'Asignado'
";
$resTotal = mysqli_query($conn, $sqlTotal);
$total_asignadas = 0;

if ($resTotal) {
    $filaTotal = mysqli_fetch_assoc($resTotal);
    $total_asignadas = (int)$filaTotal['total_asignadas'];
}

/* historial de movimientos */
$sqlHistorial = "
SELECT
    h.id_historial,
    h.tipo_movimiento,
    h.fecha_movimiento,
    c1.nombre_categoria AS categoria_anterior_nombre,
    n1.nombre_nivel AS nivel_anterior_nombre,
    c2.nombre_categoria AS categoria_nueva_nombre,
    n2.nombre_nivel AS nivel_nuevo_nombre,
    h.observacion
FROM historial_integrante h
LEFT JOIN categorias c1 ON h.categoria_anterior = c1.id_categoria
LEFT JOIN niveles n1 ON h.nivel_anterior = n1.id_nivel
LEFT JOIN categorias c2 ON h.categoria_nueva = c2.id_categoria
LEFT JOIN niveles n2 ON h.nivel_nuevo = n2.id_nivel
WHERE h.id_integrante = $id
ORDER BY h.fecha_movimiento ASC
";

$resHistorial = mysqli_query($conn, $sqlHistorial);

$historial = [];
while ($fila = mysqli_fetch_assoc($resHistorial)) {
    $historial[] = $fila;
}

echo json_encode([
    "ok" => true,
    "integrante" => $integrante,
    "total_asignadas" => $total_asignadas,
    "historial" => $historial
], JSON_UNESCAPED_UNICODE);

mysqli_close($conn);
?>