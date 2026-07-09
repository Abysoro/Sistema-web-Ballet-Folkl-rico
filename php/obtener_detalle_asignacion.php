<?php
include("conexion.php");

date_default_timezone_set("America/La_Paz");
mysqli_query($conn, "SET time_zone = '-04:00'");

function formatearTiempoEspera($segundos) {
    if ($segundos === null) {
        return "--";
    }

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
    d.id_detalle,
    i.nombre AS integrante,
    i.apellido,
    p.nombre_presentacion,
    c.nombre_categoria,
    d.fecha_ingreso_cola AS ingreso_cola,
    d.fecha_asignacion,
    d.estado_asignacion AS estado,
    d.veces_no_asignado,
    d.criterio_seleccion,
    d.tipo_asignacion,
    d.motivo_excepcion,

    CASE
        WHEN d.estado_asignacion = 'Asignado' 
             AND d.fecha_ingreso_cola IS NOT NULL
             AND d.fecha_asignacion IS NOT NULL
        THEN TIMESTAMPDIFF(SECOND, d.fecha_ingreso_cola, d.fecha_asignacion)

        WHEN d.estado_asignacion = 'En espera'
             AND d.fecha_ingreso_cola IS NOT NULL
        THEN TIMESTAMPDIFF(SECOND, d.fecha_ingreso_cola, NOW())

        ELSE NULL
    END AS tiempo_espera_segundos

FROM detalle_asignacion d
INNER JOIN integrantes i
    ON d.id_integrante = i.id_integrante
INNER JOIN presentaciones p
    ON d.id_presentacion = p.id_presentacion
INNER JOIN categorias c
    ON d.id_categoria = c.id_categoria
INNER JOIN jornadas j
    ON p.id_jornada = j.id_jornada

WHERE p.estado = 'Activa'
AND p.fecha >= CURDATE()
AND j.estado = 'Activo'

ORDER BY p.fecha ASC, p.hora_presentacion ASC, d.id_detalle ASC
";
$resultado = mysqli_query($conn, $sql);

$datos = [];

while ($fila = mysqli_fetch_assoc($resultado)) {
    $fila['tiempo_espera'] = formatearTiempoEspera($fila['tiempo_espera_segundos']);
    unset($fila['tiempo_espera_segundos']);
    $datos[] = $fila;
}

echo json_encode($datos, JSON_UNESCAPED_UNICODE);

mysqli_close($conn);
?>