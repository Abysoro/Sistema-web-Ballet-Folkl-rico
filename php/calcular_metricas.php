<?php
include "conexion.php";

/* limpiar métricas anteriores */
$conn->query("DELETE FROM metricas_cola");

$sqlPresentaciones = "
SELECT p.*
FROM presentaciones p
INNER JOIN jornadas j ON p.id_jornada = j.id_jornada
WHERE p.estado = 'Activa'
AND p.fecha >= CURDATE()
AND j.estado = 'Activo'
ORDER BY p.fecha ASC, p.hora_presentacion ASC
";
$resPresentaciones = $conn->query($sqlPresentaciones);

if (!$resPresentaciones) {
    die("Error al obtener presentaciones: " . $conn->error);
}

while ($p = $resPresentaciones->fetch_assoc()) {

    $id_presentacion = (int)$p['id_presentacion'];
    $capacidad = (int)$p['capacidad'];

    /* tasa de llegada: cuántos ingresaron a cola para esa presentación */
    $sqlLlegada = "SELECT COUNT(*) AS total
                   FROM cola_asignacion
                   WHERE id_presentacion = $id_presentacion";

    $resLlegada = $conn->query($sqlLlegada);
    if (!$resLlegada) {
        die("Error en tasa_llegada: " . $conn->error);
    }
    $tasa_llegada = (int)$resLlegada->fetch_assoc()['total'];

    /* tasa de servicio: SOLO cuántos fueron asignados */
    $sqlServicio = "SELECT COUNT(*) AS total
                    FROM detalle_asignacion
                    WHERE id_presentacion = $id_presentacion
                    AND estado_asignacion = 'Asignado'";

    $resServicio = $conn->query($sqlServicio);
    if (!$resServicio) {
        die("Error en tasa_servicio: " . $conn->error);
    }
    $tasa_servicio = (int)$resServicio->fetch_assoc()['total'];

    /* longitud de cola: cuántos siguen esperando en esa presentación */
    $sqlCola = "SELECT COUNT(*) AS total
                FROM cola_asignacion
                WHERE id_presentacion = $id_presentacion
                AND estado = 'esperando'";

    $resCola = $conn->query($sqlCola);
    if (!$resCola) {
        die("Error en longitud_cola: " . $conn->error);
    }
    $longitud_cola = (int)$resCola->fetch_assoc()['total'];

   /* tiempo de espera promedio en segundos:
   mide solo cuánto tardaron en asignarse los integrantes ya asignados */
$sqlEspera = "SELECT 
                COALESCE(
                    AVG(
                        TIMESTAMPDIFF(
                            SECOND,
                            d.fecha_ingreso_cola,
                            d.fecha_asignacion
                        )
                    ),
                    0
                ) AS promedio
              FROM detalle_asignacion d
              WHERE d.id_presentacion = $id_presentacion
              AND d.estado_asignacion = 'Asignado'
              AND d.fecha_ingreso_cola IS NOT NULL
              AND d.fecha_asignacion IS NOT NULL";

$resEspera = $conn->query($sqlEspera);

if (!$resEspera) {
    die("Error en tiempo_espera_promedio: " . $conn->error);
}

$filaEspera = $resEspera->fetch_assoc();
$tiempo_espera_promedio = round((float)$filaEspera['promedio'], 2);
    /* utilización del sistema en porcentaje */
    if ($capacidad > 0) {
        $utilizacion_sistema = ($tasa_servicio / $capacidad) * 100;

        if ($utilizacion_sistema > 100) {
            $utilizacion_sistema = 100;
        }
    } else {
        $utilizacion_sistema = 0;
    }

    $sqlInsert = "INSERT INTO metricas_cola
                  (id_presentacion, tasa_llegada, tasa_servicio, longitud_cola, tiempo_espera_promedio, utilizacion_sistema)
                  VALUES
                  ($id_presentacion, $tasa_llegada, $tasa_servicio, $longitud_cola, $tiempo_espera_promedio, $utilizacion_sistema)";

    if (!$conn->query($sqlInsert)) {
        die("Error al insertar en metricas_cola: " . $conn->error);
    }
}

echo "OK";

$conn->close();
?>