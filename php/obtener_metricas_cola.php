<?php
include("conexion.php");

header("Content-Type: application/json; charset=utf-8");

date_default_timezone_set("America/La_Paz");
$conn->query("SET time_zone = '-04:00'");

$sql = "
SELECT
    p.id_presentacion,
    p.nombre_presentacion,
    COALESCE(mc.tasa_llegada, 0) AS tasa_llegada,
    COALESCE(mc.tasa_servicio, 0) AS tasa_servicio,
    COALESCE(mc.longitud_cola, 0) AS longitud_cola,
    COALESCE(mc.tiempo_espera_promedio, 0) AS tiempo_espera_promedio,
    COALESCE(mc.utilizacion_sistema, 0) AS utilizacion_sistema,

    CASE
        WHEN p.fecha_registro IS NULL
             OR p.fecha_registro = '0000-00-00 00:00:00'
             OR p.capacidad IS NULL
             OR p.capacidad <= 0
        THEN NULL

        WHEN COALESCE(a.total_asignados, 0) >= p.capacidad
        THEN
            CASE
                WHEN a.fechas_validas = a.total_asignados
                     AND a.ultima_asignacion >= p.fecha_registro
                THEN TIMESTAMPDIFF(
                    SECOND,
                    p.fecha_registro,
                    a.ultima_asignacion
                )
                ELSE NULL
            END

        ELSE GREATEST(
            0,
            TIMESTAMPDIFF(SECOND, p.fecha_registro, NOW())
        )
    END AS tiempo_hasta_completar_segundos

FROM presentaciones p
INNER JOIN jornadas j
    ON p.id_jornada = j.id_jornada

LEFT JOIN metricas_cola mc
    ON mc.id_presentacion = p.id_presentacion

LEFT JOIN (
    SELECT
        id_presentacion,
        COUNT(*) AS total_asignados,
        COUNT(
            NULLIF(fecha_asignacion, '0000-00-00 00:00:00')
        ) AS fechas_validas,
        MAX(
            NULLIF(fecha_asignacion, '0000-00-00 00:00:00')
        ) AS ultima_asignacion
    FROM detalle_asignacion
    WHERE estado_asignacion = 'Asignado'
    GROUP BY id_presentacion
) a ON a.id_presentacion = p.id_presentacion

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