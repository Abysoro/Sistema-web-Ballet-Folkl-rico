<?php
include("conexion.php");

header("Content-Type: application/json; charset=utf-8");

$sql = "
SELECT 
    p.id_presentacion,
    p.nombre_presentacion,
    COALESCE(mc.tasa_llegada, 0) AS tasa_llegada,
    COALESCE(mc.tasa_servicio, 0) AS tasa_servicio,
    COALESCE(mc.longitud_cola, 0) AS longitud_cola,
    COALESCE(mc.tiempo_espera_promedio, 0) AS tiempo_espera_promedio,
    COALESCE(mc.utilizacion_sistema, 0) AS utilizacion_sistema
FROM presentaciones p
INNER JOIN jornadas j
    ON p.id_jornada = j.id_jornada
LEFT JOIN metricas_cola mc 
    ON mc.id_presentacion = p.id_presentacion
WHERE p.estado = 'Activa'
AND p.fecha >= CURDATE()
AND j.estado = 'Activo'
ORDER BY p.fecha ASC, p.hora_presentacion ASC
";

$resultado = mysqli_query($conn, $sql);

if (!$resultado) {
    echo json_encode([
        "ok" => false,
        "mensaje" => "Error al obtener métricas: " . mysqli_error($conn)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$datos = [];

while ($fila = mysqli_fetch_assoc($resultado)) {
    $datos[] = $fila;
}

echo json_encode($datos, JSON_UNESCAPED_UNICODE);

mysqli_close($conn);
?>