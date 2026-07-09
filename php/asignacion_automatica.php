<?php
include "conexion.php";
include "actualizar_estado_eventos.php";
date_default_timezone_set('America/La_Paz');
$conn->query("SET time_zone = '-04:00'");
header('Content-Type: application/json; charset=utf-8');

$alertasSaturacion = [];

function horaAMinutos($hora) {
    if (!$hora || $hora === "00:00:00") {
        return null;
    }

    $partes = explode(":", $hora);
    $h = isset($partes[0]) ? (int)$partes[0] : 0;
    $m = isset($partes[1]) ? (int)$partes[1] : 0;

    return ($h * 60) + $m;
}

function obtenerTrasladoMinutos($conn, $origen, $destino) {
    if (trim($origen) === trim($destino)) {
        return 0;
    }

    $origen = mysqli_real_escape_string($conn, trim($origen));
    $destino = mysqli_real_escape_string($conn, trim($destino));

    $sql = "SELECT minutos_traslado
            FROM traslado_lugares
            WHERE lugar_origen = '$origen'
              AND lugar_destino = '$destino'
            LIMIT 1";

    $res = mysqli_query($conn, $sql);

    if ($res && mysqli_num_rows($res) > 0) {
        $fila = mysqli_fetch_assoc($res);
        return (int)$fila['minutos_traslado'];
    }

    return 9999;
}

function evaluarSaturacionDia($conn, $id_integrante, $id_presentacion_nueva) {
    $id_integrante = (int)$id_integrante;
    $id_presentacion_nueva = (int)$id_presentacion_nueva;

    $sqlParam = "
        SELECT *
        FROM parametros_saturacion_dia
        ORDER BY id_parametro DESC
        LIMIT 1
    ";

    $resParam = mysqli_query($conn, $sqlParam);

    if (!$resParam || mysqli_num_rows($resParam) === 0) {
        return [
            'permitir' => false,
            'estado' => 'saturado',
            'motivo' => 'No existen parámetros de saturación configurados.'
        ];
    }

    $param = mysqli_fetch_assoc($resParam);

    /*
      Obtener la presentación nueva.
      Solo debe evaluar presentaciones activas y actuales/futuras.
    */
    $sqlNueva = "
        SELECT
            p.id_presentacion,
            p.id_jornada,
            p.nombre_presentacion,
            p.fecha,
            p.hora_presentacion,
            p.duracion_minutos,
            p.estado,
            j.nombre_jornada,
            j.lugar,
            j.hora_inicio,
            j.hora_fin,
            j.estado AS estado_jornada
        FROM presentaciones p
        INNER JOIN jornadas j
            ON p.id_jornada = j.id_jornada
        WHERE p.id_presentacion = $id_presentacion_nueva
          AND p.estado = 'Activa'
          AND p.fecha >= CURDATE()
          AND j.estado = 'Activo'
        LIMIT 1
    ";

    $resNueva = mysqli_query($conn, $sqlNueva);

    if (!$resNueva || mysqli_num_rows($resNueva) === 0) {
        return [
            'permitir' => false,
            'estado' => 'saturado',
            'motivo' => 'La presentación nueva no está activa o ya pasó de fecha.'
        ];
    }

    $nueva = mysqli_fetch_assoc($resNueva);
    $fecha = $nueva['fecha'];

    /*
      Buscar SOLO asignaciones activas del mismo día.
      No debe tomar presentaciones inactivas ni pasadas.
      Tampoco debe repetir la misma presentación nueva.
    */
    $sqlActuales = "
        SELECT
            p.id_presentacion,
            p.id_jornada,
            p.nombre_presentacion,
            p.fecha,
            p.hora_presentacion,
            p.duracion_minutos,
            p.estado,
            j.nombre_jornada,
            j.lugar,
            j.hora_inicio,
            j.hora_fin,
            j.estado AS estado_jornada
        FROM integrantes_presentacion ip
        INNER JOIN presentaciones p
            ON ip.id_presentacion = p.id_presentacion
        INNER JOIN jornadas j
            ON p.id_jornada = j.id_jornada
        WHERE ip.id_integrante = $id_integrante
          AND p.fecha = '$fecha'
          AND p.fecha >= CURDATE()
          AND p.estado = 'Activa'
          AND j.estado = 'Activo'
          AND p.id_presentacion <> $id_presentacion_nueva
    ";

    $resActuales = mysqli_query($conn, $sqlActuales);

    if (!$resActuales) {
        return [
            'permitir' => false,
            'estado' => 'saturado',
            'motivo' => 'Error al verificar saturación del integrante: ' . mysqli_error($conn)
        ];
    }

    $eventos = [];

    while ($fila = mysqli_fetch_assoc($resActuales)) {
        $eventos[] = $fila;
    }

    /*
      Agregamos la presentación nueva al análisis.
    */
    $eventos[] = $nueva;

    /*
      Ordenar por hora de jornada.
      Si luego quieres mayor precisión, se puede ordenar por hora_presentacion.
    */
    usort($eventos, function ($a, $b) {
        return strcmp($a['hora_inicio'], $b['hora_inicio']);
    });

    $jornadas = [];
    $presentacionesDia = count($eventos);
    $minutosTotales = 0;
    $presentacionesPorJornada = [];
    $motivos = [];
    $conflictoHorario = false;

    foreach ($eventos as $ev) {
        $jornadas[$ev['id_jornada']] = true;
        $minutosTotales += (int)$ev['duracion_minutos'];

        if (!isset($presentacionesPorJornada[$ev['id_jornada']])) {
            $presentacionesPorJornada[$ev['id_jornada']] = 0;
        }

        $presentacionesPorJornada[$ev['id_jornada']]++;
    }

    /*
      Verificar conflictos SOLO entre jornadas diferentes.
      Si es la misma jornada, no se debe comparar "Concurso" contra "Concurso",
      porque eso genera falso saturado.
    */
    for ($i = 0; $i < count($eventos) - 1; $i++) {
        $actual = $eventos[$i];
        $siguiente = $eventos[$i + 1];

        if ((int)$actual['id_jornada'] === (int)$siguiente['id_jornada']) {
            continue;
        }

        $finActual = horaAMinutos($actual['hora_fin']);
        $inicioSiguiente = horaAMinutos($siguiente['hora_inicio']);

        if ($finActual === null || $inicioSiguiente === null) {
            $motivos[] = "Falta hora de inicio o fin en alguna jornada del mismo día.";
            $conflictoHorario = true;
            continue;
        }

        $tiempoDisponible = $inicioSiguiente - $finActual;

        if (trim($actual['lugar']) === trim($siguiente['lugar'])) {
            $tiempoNecesario = (int)$param['min_descanso_mismo_lugar'];
        } else {
            $traslado = obtenerTrasladoMinutos($conn, $actual['lugar'], $siguiente['lugar']);

            $tiempoNecesario = $traslado
                + (int)$param['buffer_traslado_min']
                + (int)$param['tiempo_cambio_fijo_min'];
        }

        if ($tiempoDisponible < $tiempoNecesario) {
            $motivos[] = "No alcanza el tiempo entre la jornada '" . $actual['nombre_jornada'] .
                         "' (" . $actual['lugar'] . ") y la jornada '" . $siguiente['nombre_jornada'] .
                         "' (" . $siguiente['lugar'] . ").";
            $conflictoHorario = true;
        }
    }

    $jornadasDia = count($jornadas);
    $maxPorJornada = 0;

    foreach ($presentacionesPorJornada as $cantidad) {
        if ($cantidad > $maxPorJornada) {
            $maxPorJornada = $cantidad;
        }
    }

    if ($jornadasDia > (int)$param['max_jornadas_dia']) {
        $motivos[] = "Supera el máximo de jornadas permitidas en un mismo día.";
    }

    if ($presentacionesDia > (int)$param['max_presentaciones_dia']) {
        $motivos[] = "Supera el máximo de presentaciones permitidas en un mismo día.";
    }

    if ($maxPorJornada > (int)$param['max_presentaciones_por_jornada']) {
        $motivos[] = "Tiene demasiadas presentaciones dentro de una sola jornada.";
    }

    if ($minutosTotales > (int)$param['max_minutos_danza_dia']) {
        $motivos[] = "Supera el máximo de minutos de danza permitidos en el día.";
    }

    $permitir = count($motivos) === 0 && !$conflictoHorario;

    return [
        'permitir' => $permitir,
        'estado' => $permitir ? 'normal' : 'saturado',
        'motivo' => $permitir ? 'Asignación permitida.' : implode(' ', $motivos),
        'detalle' => [
            'jornadas_dia' => $jornadasDia,
            'presentaciones_dia' => $presentacionesDia,
            'presentaciones_max_en_jornada' => $maxPorJornada,
            'minutos_totales' => $minutosTotales,
            'conflicto_horario' => $conflictoHorario
        ]
    ];
}
$asignados = 0;
$presentaciones_completas = 0;
$sin_candidatos = 0;

$sqlPresentaciones = "SELECT * FROM presentaciones
WHERE estado = 'Activa'
AND fecha >= CURDATE()
ORDER BY fecha ASC, hora_presentacion ASC
";
$resPresentaciones = $conn->query($sqlPresentaciones);

if (!$resPresentaciones) {
    die("Error al obtener presentaciones: " . $conn->error);
}

if ($resPresentaciones->num_rows == 0) {
    die("No existen presentaciones registradas.");
}

while ($p = $resPresentaciones->fetch_assoc()) {

    $id_presentacion = (int)$p['id_presentacion'];
    $capacidad = (int)$p['capacidad'];
    $id_categoria = (int)$p['id_categoria'];

    /* contar ya asignados en esta presentación */
    $sqlContar = "SELECT COUNT(*) AS total
                  FROM integrantes_presentacion
                  WHERE id_presentacion = $id_presentacion";
    $resContar = $conn->query($sqlContar);

    if (!$resContar) {
        die("Error al contar asignados: " . $conn->error);
    }

    $asignados_actuales = (int)$resContar->fetch_assoc()['total'];
    $cuposDisponibles = $capacidad - $asignados_actuales;

    if ($cuposDisponibles <= 0) {
        $presentaciones_completas++;
        continue;
    }

    /* buscar integrantes de la misma categoría que aún NO estén en esa presentación */
    $sqlIntegrantes = "SELECT i.id_integrante, i.nombre, i.apellido
                        FROM integrantes i
                        WHERE i.id_categoria = $id_categoria
                        AND i.estado = 'activo'
                        AND i.id_integrante NOT IN (
                            SELECT ip.id_integrante
                            FROM integrantes_presentacion ip
                            WHERE ip.id_presentacion = $id_presentacion
                        )
                        ORDER BY i.id_integrante ASC";

    $resIntegrantes = $conn->query($sqlIntegrantes);

    if (!$resIntegrantes) {
        die("Error al obtener integrantes: " . $conn->error);
    }

    $listaIntegrantes = [];
    $saturadosDetectados = [];

   while ($i = $resIntegrantes->fetch_assoc()) {
    $id_candidato = (int)$i['id_integrante'];

    $evaluacionSaturacion = evaluarSaturacionDia($conn, $id_candidato, $id_presentacion);

    if (!$evaluacionSaturacion['permitir']) {
        $mensajeSaturacion = $i['nombre'] . " " . $i['apellido'] . ": " . $evaluacionSaturacion['motivo'];

        $saturadosDetectados[] = $mensajeSaturacion;
        $alertasSaturacion[] = [
            'id_presentacion' => $id_presentacion,
            'presentacion' => $p['nombre_presentacion'] ?? ('Presentación #' . $id_presentacion),
            'integrante' => $i['nombre'] . " " . $i['apellido'],
            'motivo' => $evaluacionSaturacion['motivo']
        ];
        continue;
    }

    $listaIntegrantes[] = $id_candidato;
}

    if (count($listaIntegrantes) == 0) {
        $sin_candidatos++;
        continue;
    }

    /* insertar candidatos en cola si no existen */
    foreach ($listaIntegrantes as $id_integrante) {

        $sqlExisteCola = "SELECT COUNT(*) AS total
                          FROM cola_asignacion
                          WHERE id_integrante = $id_integrante
                          AND id_presentacion = $id_presentacion";

        $resExisteCola = $conn->query($sqlExisteCola);

        if (!$resExisteCola) {
            die("Error al verificar cola: " . $conn->error);
        }

        $existeCola = (int)$resExisteCola->fetch_assoc()['total'];

        if ($existeCola == 0) {
            $sqlInsertCola = "INSERT INTO cola_asignacion
                              (id_integrante, id_presentacion, fecha_ingreso, estado, prioridad, prioridad_calculada, observacion, veces_no_asignado)
                              VALUES
                              ($id_integrante, $id_presentacion, NOW(), 'esperando', 0, 0, NULL, 0)";

            if (!$conn->query($sqlInsertCola)) {
                die("Error al insertar en cola_asignacion: " . $conn->error);
            }
        }
    }

    /* seleccionar solo los cupos disponibles */
    $sqlSeleccion = "SELECT id_cola, id_integrante, fecha_ingreso
                     FROM cola_asignacion
                     WHERE id_presentacion = $id_presentacion
                     AND estado = 'esperando'
                     ORDER BY fecha_ingreso ASC, id_cola ASC
                     LIMIT $cuposDisponibles";

    $resSeleccion = $conn->query($sqlSeleccion);

    if (!$resSeleccion) {
        die("Error al seleccionar desde cola: " . $conn->error);
    }

    while ($s = $resSeleccion->fetch_assoc()) {

    $id_cola = (int)$s['id_cola'];
    $id_integrante = (int)$s['id_integrante'];
    $fecha_ingreso_cola = $s['fecha_ingreso'];
    $fecha_asignacion = date("Y-m-d H:i:s");

    $sqlTiempo = "SELECT TIMESTAMPDIFF(MINUTE, '$fecha_ingreso_cola', '$fecha_asignacion') AS minutos";
    $resTiempo = $conn->query($sqlTiempo);

    if (!$resTiempo) {
        die("Error al calcular tiempo de espera: " . $conn->error);
    }

    $tiempo_espera = (int)$resTiempo->fetch_assoc()['minutos'];

    $sqlUpdateCola = "UPDATE cola_asignacion
                      SET estado = 'asignado'
                      WHERE id_cola = $id_cola";

    if (!$conn->query($sqlUpdateCola)) {
        die("Error al actualizar estado de cola: " . $conn->error);
    }

    $sqlExisteIP = "SELECT COUNT(*) AS total
                    FROM integrantes_presentacion
                    WHERE id_integrante = $id_integrante
                    AND id_presentacion = $id_presentacion";

    $resExisteIP = $conn->query($sqlExisteIP);

    if (!$resExisteIP) {
        die("Error al verificar integrantes_presentacion: " . $conn->error);
    }

    $existeIP = (int)$resExisteIP->fetch_assoc()['total'];

    if ($existeIP == 0) {
        $sqlInsertIP = "INSERT INTO integrantes_presentacion (id_integrante, id_presentacion)
                        VALUES ($id_integrante, $id_presentacion)";

        if (!$conn->query($sqlInsertIP)) {
            die("Error al insertar en integrantes_presentacion: " . $conn->error);
        }

        $estado_asignacion = "Asignado";
        $veces_no_asignado = 0;
        $criterio_seleccion = "Asignado por orden de cola y capacidad disponible";

        /* si ya existía registro en espera, ACTUALIZARLO */
        $sqlDetalleExistente = "SELECT id_detalle, fecha_ingreso_cola, estado_asignacion
                                FROM detalle_asignacion
                                WHERE id_integrante = $id_integrante
                                AND id_presentacion = $id_presentacion
                                ORDER BY id_detalle DESC
                                LIMIT 1";

        $resDetalleExistente = $conn->query($sqlDetalleExistente);

        if (!$resDetalleExistente) {
            die("Error al verificar detalle existente: " . $conn->error);
        }

        if ($resDetalleExistente->num_rows > 0) {
            $detalleExistente = $resDetalleExistente->fetch_assoc();
            $id_detalle = (int)$detalleExistente['id_detalle'];
            $fecha_ingreso_real = $detalleExistente['fecha_ingreso_cola'];

            $sqlTiempoReal = "SELECT TIMESTAMPDIFF(MINUTE, '$fecha_ingreso_real', '$fecha_asignacion') AS minutos";
            $resTiempoReal = $conn->query($sqlTiempoReal);

            if (!$resTiempoReal) {
                die("Error al recalcular tiempo de espera: " . $conn->error);
            }

            $tiempo_espera_real = (int)$resTiempoReal->fetch_assoc()['minutos'];

            $sqlUpdateDetalle = "UPDATE detalle_asignacion
                                 SET fecha_asignacion = '$fecha_asignacion',
                                     tiempo_espera = $tiempo_espera_real,
                                     estado_asignacion = '$estado_asignacion',
                                     veces_no_asignado = $veces_no_asignado,
                                     criterio_seleccion = '$criterio_seleccion'
                                 WHERE id_detalle = $id_detalle";

            if (!$conn->query($sqlUpdateDetalle)) {
                die("Error al actualizar detalle_asignacion: " . $conn->error);
            }
        } else {
            $sqlDetalle = "INSERT INTO detalle_asignacion
                           (id_integrante, id_presentacion, id_categoria, fecha_ingreso_cola, fecha_asignacion, tiempo_espera, estado_asignacion, veces_no_asignado, criterio_seleccion)
                           VALUES
                           ($id_integrante, $id_presentacion, $id_categoria, '$fecha_ingreso_cola', '$fecha_asignacion', $tiempo_espera, '$estado_asignacion', $veces_no_asignado, '$criterio_seleccion')";

            if (!$conn->query($sqlDetalle)) {
                die("Error al insertar en detalle_asignacion: " . $conn->error);
            }
        }

        $asignados++;
    }
}

/* registrar en detalle_asignacion a los que quedaron en espera */
/* ESTE BLOQUE DEBE QUEDAR DENTRO DEL while ($p = ...) */
$sqlEspera = "SELECT id_cola, id_integrante, fecha_ingreso
              FROM cola_asignacion
              WHERE id_presentacion = $id_presentacion
              AND estado = 'esperando'";

$resEspera = $conn->query($sqlEspera);

if (!$resEspera) {
    die("Error al obtener integrantes en espera: " . $conn->error);
}

while ($e = $resEspera->fetch_assoc()) {

    $id_integrante_espera = (int)$e['id_integrante'];
    $fecha_ingreso_espera = $e['fecha_ingreso'];

    /* verificar que no exista ya en detalle_asignacion para esta presentación */
    $sqlExisteDetalle = "SELECT COUNT(*) AS total
                         FROM detalle_asignacion
                         WHERE id_integrante = $id_integrante_espera
                         AND id_presentacion = $id_presentacion";

    $resExisteDetalle = $conn->query($sqlExisteDetalle);

    if (!$resExisteDetalle) {
        die("Error al verificar detalle_asignacion: " . $conn->error);
    }

    $existeDetalle = (int)$resExisteDetalle->fetch_assoc()['total'];

    if ($existeDetalle == 0) {

        $estado_asignacion = "En espera";
        $veces_no_asignado = 1;
        $criterio_seleccion = "Quedó en espera por falta de cupo en la presentación";

        /* OJO: fecha_asignacion y tiempo_espera deben quedar NULL
           para que el tiempo siga subiendo dinámicamente */
        $sqlDetalleEspera = "INSERT INTO detalle_asignacion
                            (id_integrante, id_presentacion, id_categoria, fecha_ingreso_cola, fecha_asignacion, tiempo_espera, estado_asignacion, veces_no_asignado, criterio_seleccion)
                            VALUES
                            ($id_integrante_espera, $id_presentacion, $id_categoria, '$fecha_ingreso_espera', NULL, NULL, '$estado_asignacion', $veces_no_asignado, '$criterio_seleccion')";

        if (!$conn->query($sqlDetalleEspera)) {
            die("Error al insertar detalle en espera: " . $conn->error);
        }
    }
}
    echo "OK";
} else {
    echo "No se realizaron nuevas asignaciones. Las presentaciones ya están completas o no existen más integrantes disponibles.";
}

$conn->close();

?>


