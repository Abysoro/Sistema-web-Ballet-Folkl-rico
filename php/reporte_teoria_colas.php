<?php
include "conexion.php";

header("Content-Type: application/json; charset=utf-8");

date_default_timezone_set("America/La_Paz");
mysqli_query($conn, "SET time_zone = '-04:00'");

function formatearTiempoColas($segundos) {
    $segundos = max(0, (int)$segundos);

    if ($segundos === 0) {
        return "0 seg";
    }

    $dias = intdiv($segundos, 86400);
    $segundos = $segundos % 86400;

    $horas = intdiv($segundos, 3600);
    $segundos = $segundos % 3600;

    $minutos = intdiv($segundos, 60);
    $segRestantes = $segundos % 60;

    if ($dias > 0) {
    $texto = $dias . ($dias === 1 ? " día" : " días");

    if ($horas > 0) {
        $texto .= " " . $horas . " h";
    }

    if ($minutos > 0) {
        $texto .= " " . $minutos . " min";
    }

    return $texto;
}
    if ($horas > 0) {
        $texto = $horas . " h";
        if ($minutos > 0) {
            $texto .= " " . $minutos . " min";
        }
        if ($segRestantes > 0) {
            $texto .= " " . $segRestantes . " seg";
        }
        return $texto;
    }

    if ($minutos > 0) {
        $texto = $minutos . " min";
        if ($segRestantes > 0) {
            $texto .= " " . $segRestantes . " seg";
        }
        return $texto;
    }

    return $segRestantes . " seg";
}

$sql = "
SELECT
    p.id_presentacion,
    p.nombre_presentacion,
    c.nombre_categoria,
    p.capacidad,
    p.fecha,
    p.hora_presentacion,
    p.lugar,
    p.estado AS estado_presentacion,
    COALESCE(j.estado, 'Sin jornada') AS estado_jornada,

    COUNT(d.id_detalle) AS tasa_llegada,

    SUM(
        CASE 
            WHEN d.estado_asignacion = 'Asignado' 
            THEN 1 
            ELSE 0 
        END
    ) AS tasa_servicio,

    SUM(
        CASE 
            WHEN d.estado_asignacion = 'En espera' 
            THEN 1 
            ELSE 0 
        END
    ) AS longitud_cola,

    CASE
    WHEN p.fecha_registro IS NULL
         OR p.fecha_registro = '0000-00-00 00:00:00'
         OR p.capacidad IS NULL
         OR p.capacidad <= 0
    THEN NULL

    WHEN SUM(
        CASE WHEN d.estado_asignacion = 'Asignado'
             THEN 1 ELSE 0 END
    ) >= p.capacidad
    THEN
        CASE
            WHEN SUM(
                CASE
                    WHEN d.estado_asignacion = 'Asignado'
                         AND d.fecha_asignacion IS NOT NULL
                         AND d.fecha_asignacion <> '0000-00-00 00:00:00'
                    THEN 1 ELSE 0
                END
            ) = SUM(
                CASE WHEN d.estado_asignacion = 'Asignado'
                     THEN 1 ELSE 0 END
            )
            AND MAX(
                CASE WHEN d.estado_asignacion = 'Asignado'
                     THEN d.fecha_asignacion END
            ) >= p.fecha_registro
            THEN TIMESTAMPDIFF(
                SECOND,
                p.fecha_registro,
                MAX(
                    CASE WHEN d.estado_asignacion = 'Asignado'
                         THEN d.fecha_asignacion END
                )
            )
            ELSE NULL
        END

    ELSE GREATEST(
        0,
        TIMESTAMPDIFF(SECOND, p.fecha_registro, NOW())
    )
END AS tiempo_hasta_completar_segundos

FROM presentaciones p
INNER JOIN categorias c
    ON p.id_categoria = c.id_categoria
LEFT JOIN jornadas j
    ON p.id_jornada = j.id_jornada
LEFT JOIN detalle_asignacion d
    ON d.id_presentacion = p.id_presentacion

GROUP BY
    p.id_presentacion,
    p.fecha_registro,
    p.nombre_presentacion,
    c.nombre_categoria,
    p.capacidad,
    p.fecha,
    p.hora_presentacion,
    p.lugar,
    p.estado,
    j.estado

HAVING COUNT(d.id_detalle) > 0
    OR (
        estado_presentacion = 'Activa'
        AND p.fecha >= CURDATE()
        AND estado_jornada = 'Activo'
    )

ORDER BY
    FIELD(c.nombre_categoria, 'Niños', 'Juvenil', 'Juveniles', 'Aspirantes', 'Elenco Oficial'),
    c.nombre_categoria ASC,
    p.fecha DESC,
    p.hora_presentacion DESC,
    p.nombre_presentacion ASC
";

$res = $conn->query($sql);

if (!$res) {
    echo json_encode([
        "ok" => false,
        "mensaje" => "Error al obtener reporte de teoría de colas: " . $conn->error
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$datos = [];

while ($row = $res->fetch_assoc()) {
    $capacidad = (int)$row["capacidad"];
    $tasaLlegada = (int)$row["tasa_llegada"];
    $tasaServicio = (int)$row["tasa_servicio"];
    $longitudCola = (int)$row["longitud_cola"];
    $tiempoCompletar = $row["tiempo_hasta_completar_segundos"];

    if ($capacidad > 0) {
        $utilizacion = ($tasaServicio / $capacidad) * 100;
        if ($utilizacion > 100) {
            $utilizacion = 100;
        }
    } else {
        $utilizacion = 0;
    }

    $datos[] = [
        "presentacion" => $row["nombre_presentacion"],
        "categoria" => $row["nombre_categoria"],
        "capacidad" => $capacidad,
        "fecha" => $row["fecha"],
        "hora" => $row["hora_presentacion"],
        "lugar" => $row["lugar"],
        "estado_presentacion" => $row["estado_presentacion"],
        "estado_jornada" => $row["estado_jornada"],

        "tasa_llegada" => number_format($tasaLlegada, 2),
        "tasa_servicio" => number_format($tasaServicio, 2),
        "longitud_cola" => $longitudCola,
        "tiempo_hasta_completar" => $tiempoCompletar === null
    ? "Sin dato"
    : formatearTiempoColas((int)$tiempoCompletar),
        "utilizacion_sistema" => number_format($utilizacion, 2) . "%"
    ];
}

echo json_encode([
    "ok" => true,
    "datos" => $datos
], JSON_UNESCAPED_UNICODE);

$conn->close();
?>