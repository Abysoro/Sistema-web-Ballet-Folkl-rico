<?php
include "conexion.php";
function integranteEstaSaturado($conn, $id_integrante) {
    $sqlParam = "
        SELECT max_presentaciones_dia
        FROM parametros_saturacion_dia
        ORDER BY id_parametro DESC
        LIMIT 1
    ";

    $resParam = $conn->query($sqlParam);

    if (!$resParam || $resParam->num_rows === 0) {
        $maxPresentaciones = 6;
    } else {
        $param = $resParam->fetch_assoc();
        $maxPresentaciones = (int)$param["max_presentaciones_dia"];
    }

    $sqlConteo = "
        SELECT COUNT(*) AS total
        FROM detalle_asignacion d
        INNER JOIN presentaciones p
            ON d.id_presentacion = p.id_presentacion
        INNER JOIN jornadas j
            ON p.id_jornada = j.id_jornada
        WHERE d.id_integrante = $id_integrante
        AND d.estado_asignacion = 'Asignado'
        AND p.estado = 'Activa'
        AND j.estado = 'Activo'
    ";

    $resConteo = $conn->query($sqlConteo);

    if (!$resConteo) {
        return [
            "saturado" => false,
            "motivo" => "No se pudo verificar saturación."
        ];
    }

    $totalAsignaciones = (int)$resConteo->fetch_assoc()["total"];

    /*
      Regla:
      Si el máximo es 6, se permite hasta 6.
      Si ya tiene 6 o más, no se le permite otra asignación.
    */
    if ($totalAsignaciones >= $maxPresentaciones) {
        return [
            "saturado" => true,
            "motivo" => "El integrante ya tiene $totalAsignaciones presentaciones activas. Límite permitido: $maxPresentaciones."
        ];
    }

    return [
        "saturado" => false,
        "motivo" => "Disponible para asignación."
    ];
}

date_default_timezone_set('America/La_Paz');
$conn->query("SET time_zone = '-04:00'");
header('Content-Type: application/json; charset=utf-8');

$alertasSaturacion = [];
$presentaciones_completas = 0;
$sin_candidatos = 0;

$asignados = 0;

/* =========================
   FUNCIONES AUXILIARES
========================= */
function limitar($valor, $min = 0, $max = 1) {
    if ($valor < $min) return $min;
    if ($valor > $max) return $max;
    return $valor;
}

function textoSQL($conn, $texto) {
    return $conn->real_escape_string($texto);
}

function obtenerEscenario($nivel_minimo, $cuposDisponibles, $cantidadCandidatos, $hayEquidad) {
    if ($hayEquidad) {
        return "Prioridad por equidad";
    }

    if ($nivel_minimo >= 3) {
        return "Prioridad por nivel";
    }

    if ($cantidadCandidatos > $cuposDisponibles) {
        return "Alta demanda";
    }

    return "Demanda baja";
}

function construirMotivoSegunEscenario($escenario, $decision, $cumpleNivelSuperior, $puntajeTotal, $puntajeCorte = null, $motivoDesempate = "") {
    $lineas = [];
    $lineas[] = "Escenario: " . $escenario;

    if ($escenario === "Demanda baja") {
        if ($decision === "Asignado") {
            $lineas[] = "Motivo principal: existe capacidad suficiente para la cantidad de candidatos.";
            $lineas[] = "Análisis: la congestión es baja y el integrante cumple los requisitos mínimos de la presentación.";
        } else {
            $lineas[] = "Motivo principal: no fue asignado por condiciones posteriores del proceso.";
        }
    }

    if ($escenario === "Alta demanda") {
        if ($decision === "Asignado") {
            $lineas[] = "Motivo principal: la cantidad de candidatos superó los cupos disponibles.";
            $lineas[] = "Análisis: fue seleccionado por mayor puntaje total ponderado.";

            if ($cumpleNivelSuperior) {
                $lineas[] = "Criterio adicional: presentó un nivel superior al mínimo requerido.";
            }

            if ($puntajeCorte !== null) {
                $lineas[] = "Referencia: su puntaje total (" . number_format($puntajeTotal, 2) . ") alcanzó o superó el puntaje de corte (" . number_format($puntajeCorte, 2) . ").";
            }
        } else {
            $lineas[] = "Motivo principal: la cantidad de candidatos superó los cupos disponibles.";

            if ($puntajeCorte !== null) {
                if ($puntajeTotal < $puntajeCorte) {
                    $lineas[] = "Análisis: quedó en espera porque su puntaje total (" . number_format($puntajeTotal, 2) . ") fue menor al puntaje de corte (" . number_format($puntajeCorte, 2) . ").";
                } elseif ($puntajeTotal == $puntajeCorte) {
                    $lineas[] = "Análisis: obtuvo el mismo puntaje total que otros candidatos (" . number_format($puntajeTotal, 2) . "), pero quedó en espera por criterios de desempate.";
                    if ($motivoDesempate !== "") {
                        $lineas[] = "Desempate aplicado: " . $motivoDesempate . ".";
                    }
                } else {
                    $lineas[] = "Análisis: quedó en espera por priorización de otros candidatos.";
                }
            } else {
                $lineas[] = "Análisis: quedó en espera por menor prioridad frente a otros candidatos.";
            }
        }
    }

    if ($escenario === "Prioridad por nivel") {
        if ($decision === "Asignado") {
            $lineas[] = "Motivo principal: se dio mayor importancia al criterio nivel.";

            if ($cumpleNivelSuperior) {
                $lineas[] = "Análisis: presentó un nivel superior al mínimo requerido.";
            } else {
                $lineas[] = "Análisis: cumplió exactamente el nivel mínimo requerido.";
            }

            $lineas[] = "Resultado: fue seleccionado por mejor ajuste técnico a la presentación.";
        } else {
            $lineas[] = "Motivo principal: se dio mayor importancia al criterio nivel.";
            $lineas[] = "Análisis: otros candidatos presentaron mejor ajuste al nivel requerido.";
        }
    }

    if ($escenario === "Prioridad por equidad") {
        if ($decision === "Asignado") {
            $lineas[] = "Motivo principal: se priorizó a integrantes que quedaron en espera anteriormente.";
            $lineas[] = "Análisis: se buscó equilibrar oportunidades de participación.";
            $lineas[] = "Resultado: mantuvo prioridad suficiente para ser asignado.";
        } else {
            $lineas[] = "Motivo principal: se priorizó a integrantes con mayor necesidad de equidad.";
            $lineas[] = "Análisis: otros candidatos acumulaban mayor prioridad por espera previa.";
        }
    }

    return implode("\n", $lineas);
}

function construirDetallePuntajes($puntaje_nivel, $puntaje_rapidez, $puntaje_antiguedad, $puntaje_equidad, $puntaje_experiencia, $puntaje_total) {
    $lineas = [];
    $lineas[] = "Detalle de puntajes:";
    $lineas[] = "- Nivel: " . number_format($puntaje_nivel, 2);
    $lineas[] = "- Rapidez: " . number_format($puntaje_rapidez, 2);
    $lineas[] = "- Antigüedad: " . number_format($puntaje_antiguedad, 2);
    $lineas[] = "- Equidad: " . number_format($puntaje_equidad, 2);
    $lineas[] = "- Experiencia: " . number_format($puntaje_experiencia, 2);
    $lineas[] = "- Puntaje total: " . number_format($puntaje_total, 2);

    return implode("\n", $lineas);
}

function obtenerMotivoDesempate($a, $b) {
    if ($a['puntaje_equidad'] != $b['puntaje_equidad']) {
        return "se priorizó al candidato con mayor puntaje de equidad";
    }

    if ($a['puntaje_nivel'] != $b['puntaje_nivel']) {
        return "se priorizó al candidato con mayor puntaje en nivel";
    }

    if ($a['puntaje_rapidez'] != $b['puntaje_rapidez']) {
        return "se priorizó al candidato con mayor puntaje en rapidez";
    }

    if ($a['puntaje_antiguedad'] != $b['puntaje_antiguedad']) {
        return "se priorizó al candidato con mayor puntaje en antigüedad";
    }

    if ($a['puntaje_experiencia'] != $b['puntaje_experiencia']) {
        return "se priorizó al candidato con mayor puntaje en experiencia";
    }

    return "se aplicó el orden de priorización configurado por el sistema";
}


function nombreCategoria($id) {
    $map = [
        1 => 'Niños',
        2 => 'Juvenil',
        3 => 'Aspirantes',
        4 => 'Elenco Oficial'
    ];
    return $map[$id] ?? 'Categoría desconocida';
}

function nombreNivel($id) {
    $map = [
        1 => 'básico',
        2 => 'intermedio',
        3 => 'avanzado'
    ];
    return $map[$id] ?? 'desconocido';
}

function generarTipoYMotivoExcepcion($idCategoriaReq, $idNivelReq, $idCategoriaCand, $idNivelCand) {
    if ($idCategoriaCand == $idCategoriaReq && $idNivelCand >= $idNivelReq) {
        return ['normal', null];
    }

    if ($idCategoriaCand == $idCategoriaReq && $idNivelCand < $idNivelReq) {
        return [
            'mismo_categoria_nivel_inferior',
            "No hubo suficientes integrantes de la categoría " . nombreCategoria($idCategoriaReq) .
            " con el nivel requerido " . nombreNivel($idNivelReq) .
            ", se asignó un integrante de la misma categoría con nivel " . nombreNivel($idNivelCand) . "."
        ];
    }

    return [
        'apoyo_categoria_externa',
        "No hubo suficientes integrantes de la categoría " . nombreCategoria($idCategoriaReq) .
        ", se asignó " . strtolower(nombreCategoria($idCategoriaCand)) . " " . nombreNivel($idNivelCand) .
        " como apoyo excepcional."
    ];
}

function reglaSuplencia($idCategoriaReq, $idCategoriaCand, $idNivelCand) {
    /* misma categoría siempre primero */
    if ($idCategoriaCand == $idCategoriaReq) {
        return [
            'permitido' => true,
            'prioridad' => 1,
            'tipo' => 'misma_categoria',
            'motivo' => 'Suplente de la misma categoría.'
        ];
    }

    /* ELENCO OFICIAL -> Juvenil cualquier nivel */
    if ($idCategoriaReq == 4) {
        if ($idCategoriaCand == 2) {
            return [
                'permitido' => true,
                'prioridad' => 2,
                'tipo' => 'categoria_alternativa',
                'motivo' => 'Suplente alternativo: Juvenil de cualquier nivel para Elenco Oficial.'
            ];
        }
    }

    /* JUVENIL -> Elenco Oficial cualquier nivel o Aspirantes avanzado */
    if ($idCategoriaReq == 2) {
        if ($idCategoriaCand == 4) {
            return [
                'permitido' => true,
                'prioridad' => 2,
                'tipo' => 'categoria_alternativa',
                'motivo' => 'Suplente alternativo: Elenco Oficial de cualquier nivel para Juvenil.'
            ];
        }

        if ($idCategoriaCand == 3 && $idNivelCand == 3) {
            return [
                'permitido' => true,
                'prioridad' => 3,
                'tipo' => 'categoria_alternativa',
                'motivo' => 'Suplente alternativo: Aspirantes nivel avanzado para Juvenil.'
            ];
        }
    }

    /* ASPIRANTES -> Juvenil básico o intermedio */
    if ($idCategoriaReq == 3) {
        if ($idCategoriaCand == 2 && in_array((int)$idNivelCand, [1, 2], true)) {
            return [
                'permitido' => true,
                'prioridad' => 2,
                'tipo' => 'categoria_alternativa',
                'motivo' => 'Suplente alternativo: Juvenil nivel básico o intermedio para Aspirantes.'
            ];
        }
    }

    /* NIÑOS -> solo niños (ya cubierto por misma categoría) */
    return [
        'permitido' => false,
        'prioridad' => 99,
        'tipo' => 'no_permitido',
        'motivo' => 'No permitido como suplente para esta categoría.'
    ];
}

function generarSuplentesPresentacion(
    $conn,
    $id_presentacion,
    $id_categoria,
    $nivel_minimo,
    $rapidez_minima,
    $antiguedad_minima,
    $peso_nivel,
    $peso_rapidez,
    $peso_antiguedad,
    $peso_equidad,
    $peso_experiencia
) {
    $id_presentacion = (int)$id_presentacion;
    $id_categoria = (int)$id_categoria;
    $nivel_minimo = (int)$nivel_minimo;

    /* borrar suplentes anteriores de esa presentación */
    $conn->query("DELETE FROM suplentes_presentacion WHERE id_presentacion = $id_presentacion");

    $sql = "
        SELECT
            i.id_integrante,
            i.nombre,
            i.apellido,
            i.id_categoria,
            i.id_nivel,
            COALESCE(ci.rapidez_aprendizaje, 0) AS rapidez_aprendizaje,
            COALESCE(ci.anios_antiguedad, 0) AS anios_antiguedad,
            COALESCE(ci.cantidad_presentaciones, 0) AS cantidad_presentaciones,
            (
                SELECT COUNT(*)
                FROM detalle_asignacion da
                WHERE da.id_integrante = i.id_integrante
                  AND da.estado_asignacion = 'En espera'
            ) AS veces_no_asignado
        FROM integrantes i
        INNER JOIN criterios_integrante ci
            ON i.id_integrante = ci.id_integrante
        WHERE i.estado = 'activo'
          AND i.id_integrante NOT IN (
              SELECT ip.id_integrante
              FROM integrantes_presentacion ip
              WHERE ip.id_presentacion = $id_presentacion
          )
    ";

    if (!is_null($rapidez_minima) && $rapidez_minima !== '') {
        $sql .= " AND ci.rapidez_aprendizaje >= " . (int)$rapidez_minima;
    }

    if (!is_null($antiguedad_minima) && $antiguedad_minima !== '') {
        $sql .= " AND ci.anios_antiguedad >= " . (int)$antiguedad_minima;
    }

    $res = $conn->query($sql);

    if (!$res) {
        die("Error al generar suplentes: " . $conn->error);
    }

    $suplentes = [];

    while ($i = $res->fetch_assoc()) {
        $regla = reglaSuplencia(
            $id_categoria,
            (int)$i['id_categoria'],
            (int)$i['id_nivel']
        );

        if (!$regla['permitido']) {
            continue;
        }

        $evaluacionSaturacion = evaluarSaturacionDia($conn, (int)$i['id_integrante'], $id_presentacion);
        if (!$evaluacionSaturacion['permitir']) {
            continue;
        }

        $id_nivel = (int)$i['id_nivel'];
        $rapidez = (int)$i['rapidez_aprendizaje'];
        $antiguedad = (int)$i['anios_antiguedad'];
        $cantidad_presentaciones = (int)$i['cantidad_presentaciones'];
        $veces_no_asignado = (int)$i['veces_no_asignado'];

        $base_nivel = ($id_nivel >= $nivel_minimo) ? 1.00 : 0.80;
        $base_rapidez = limitar($rapidez / 5);
        $base_antiguedad = limitar($antiguedad / 10);
        $base_equidad = limitar($veces_no_asignado / 4);
        $base_experiencia = limitar((100 - $cantidad_presentaciones) / 100);

        $puntaje_nivel = round($base_nivel * $peso_nivel, 2);
        $puntaje_rapidez = round($base_rapidez * $peso_rapidez, 2);
        $puntaje_antiguedad = round($base_antiguedad * $peso_antiguedad, 2);
        $puntaje_equidad = round($base_equidad * $peso_equidad, 2);
        $puntaje_experiencia = round($base_experiencia * $peso_experiencia, 2);

        $puntaje_total = round(
            $puntaje_nivel +
            $puntaje_rapidez +
            $puntaje_antiguedad +
            $puntaje_equidad +
            $puntaje_experiencia,
            2
        );

        $suplentes[] = [
            'id_integrante' => (int)$i['id_integrante'],
            'id_categoria' => (int)$i['id_categoria'],
            'id_nivel' => (int)$i['id_nivel'],
            'prioridad_suplente' => (int)$regla['prioridad'],
            'tipo_suplencia' => $regla['tipo'],
            'motivo_suplencia' => $regla['motivo'],
            'puntaje_total' => $puntaje_total,
            'puntaje_equidad' => $puntaje_equidad,
            'puntaje_nivel' => $puntaje_nivel,
            'puntaje_rapidez' => $puntaje_rapidez,
            'puntaje_antiguedad' => $puntaje_antiguedad,
            'puntaje_experiencia' => $puntaje_experiencia
        ];
    }

    usort($suplentes, function ($a, $b) {
        if ($a['prioridad_suplente'] != $b['prioridad_suplente']) {
            return $a['prioridad_suplente'] <=> $b['prioridad_suplente'];
        }

        if ($b['puntaje_total'] != $a['puntaje_total']) {
            return $b['puntaje_total'] <=> $a['puntaje_total'];
        }

        if ($b['puntaje_equidad'] != $a['puntaje_equidad']) {
            return $b['puntaje_equidad'] <=> $a['puntaje_equidad'];
        }

        if ($b['puntaje_nivel'] != $a['puntaje_nivel']) {
            return $b['puntaje_nivel'] <=> $a['puntaje_nivel'];
        }

        if ($b['puntaje_rapidez'] != $a['puntaje_rapidez']) {
            return $b['puntaje_rapidez'] <=> $a['puntaje_rapidez'];
        }

        if ($b['puntaje_antiguedad'] != $a['puntaje_antiguedad']) {
            return $b['puntaje_antiguedad'] <=> $a['puntaje_antiguedad'];
        }

        return $b['puntaje_experiencia'] <=> $a['puntaje_experiencia'];
    });

    $suplentes = array_slice($suplentes, 0, 3);

    $orden = 1;
    foreach ($suplentes as $s) {
        $motivo = $conn->real_escape_string($s['motivo_suplencia']);

        $sqlInsert = "INSERT INTO suplentes_presentacion
                      (
                        id_presentacion,
                        id_integrante,
                        orden_suplente,
                        categoria_origen,
                        nivel_origen,
                        tipo_suplencia,
                        motivo_suplencia,
                        estado
                      )
                      VALUES
                      (
                        $id_presentacion,
                        {$s['id_integrante']},
                        $orden,
                        {$s['id_categoria']},
                        {$s['id_nivel']},
                        '{$s['tipo_suplencia']}',
                        '$motivo',
                        'disponible'
                      )";

        if (!$conn->query($sqlInsert)) {
            die("Error al guardar suplente: " . $conn->error);
        }

        $orden++;
    }
}

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
    /* =========================================================
   VALIDACIÓN GLOBAL DE PRESENTACIONES ACTIVAS
   Bloquea aunque tenga 1 sola jornada.
   Si max_presentaciones_dia = 6, al intentar llegar a 6 ya se bloquea.
========================================================= */

$maxPresentacionesGlobal = (int)$param['max_presentaciones_dia'];

$sqlTotalActivo = "
    SELECT COUNT(DISTINCT ip.id_presentacion) AS total
    FROM integrantes_presentacion ip
    INNER JOIN presentaciones p
        ON ip.id_presentacion = p.id_presentacion
    INNER JOIN jornadas j
        ON p.id_jornada = j.id_jornada
    WHERE ip.id_integrante = $id_integrante
    AND p.estado = 'Activa'
    AND p.fecha >= CURDATE()
    AND j.estado = 'Activo'
    AND p.id_presentacion <> $id_presentacion_nueva
";

$resTotalActivo = mysqli_query($conn, $sqlTotalActivo);

if (!$resTotalActivo) {
    return [
        'permitir' => false,
        'estado' => 'saturado',
        'motivo' => 'Error al verificar presentaciones activas: ' . mysqli_error($conn)
    ];
}

$totalActualActivo = (int)mysqli_fetch_assoc($resTotalActivo)['total'];
$totalConNuevaPresentacion = $totalActualActivo + 1;

if ($maxPresentacionesGlobal > 0 && $totalConNuevaPresentacion >= $maxPresentacionesGlobal) {
    return [
        'permitir' => false,
        'estado' => 'saturado',
        'motivo' => "El integrante alcanzaría el límite de $maxPresentacionesGlobal presentaciones activas en total. Actualmente tiene $totalActualActivo y esta nueva asignación llegaría a $totalConNuevaPresentacion."
    ];
}

    /* Obtener la presentación nueva SOLO si está activa */
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
      Buscar asignaciones actuales del integrante.
      IMPORTANTE:
      Solo toma presentaciones activas, futuras/actuales y de la misma fecha.
      Ya no toma Concurso/Oruro si está inactivo o pasado.
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
            'motivo' => 'Error al verificar saturación: ' . mysqli_error($conn)
        ];
    }

    $eventos = [];

    while ($fila = mysqli_fetch_assoc($resActuales)) {
        $eventos[] = $fila;
    }

    /* Agregar la presentación nueva al análisis */
    $eventos[] = $nueva;

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
      Revisar conflictos entre jornadas diferentes.
      Si es la misma jornada, NO se compara Concurso contra Concurso.
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

            $tiempoNecesario =
                $traslado +
                (int)$param['buffer_traslado_min'] +
                (int)$param['tiempo_cambio_fijo_min'];
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

    if ($jornadasDia >= (int)$param['max_jornadas_dia']) {
    $motivos[] = "Alcanza el máximo de jornadas permitidas en un mismo día.";
}

if ($presentacionesDia >= (int)$param['max_presentaciones_dia']) {
    $motivos[] = "Alcanza el máximo de presentaciones permitidas en un mismo día.";
}


if ($maxPorJornada > (int)$param['max_presentaciones_por_jornada']) {
    $motivos[] = "Supera el máximo de presentaciones dentro de una sola jornada.";
}

if ($minutosTotales >= (int)$param['max_minutos_danza_dia']) {
    $motivos[] = "Alcanza el máximo de minutos de danza permitidos en el día.";
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

/* =========================
   1. OBTENER PESOS
========================= */
$sqlPesos = "SELECT * FROM pesos_criterios LIMIT 1";
$resPesos = $conn->query($sqlPesos);

if (!$resPesos || $resPesos->num_rows == 0) {
    die("Error: no existen pesos en la tabla pesos_criterios");
}

$pesos = $resPesos->fetch_assoc();

$peso_nivel = (float)$pesos['peso_nivel'];
$peso_rapidez = (float)$pesos['peso_rapidez'];
$peso_antiguedad = (float)$pesos['peso_antiguedad'];
$peso_equidad = (float)$pesos['peso_equidad'];
$peso_experiencia = (float)$pesos['peso_experiencia'];

/* =========================
   2. OBTENER PRESENTACIONES
========================= */
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
    die("Error al obtener presentaciones activas: " . $conn->error);
}

if ($resPresentaciones->num_rows == 0) {
    die("No existen presentaciones activas para asignar.");
}
/* =========================
   3. RECORRER PRESENTACIONES
========================= */
while ($p = $resPresentaciones->fetch_assoc()) {

    $id_presentacion = (int)$p['id_presentacion'];
    $id_categoria = (int)$p['id_categoria'];
    $capacidad = (int)$p['capacidad'];

    /* -------------------------
       3.1 Ver cuántos ya están asignados
    -------------------------- */
    $sqlContarAsignados = "SELECT COUNT(*) AS total
                           FROM integrantes_presentacion
                           WHERE id_presentacion = $id_presentacion";
    $resContarAsignados = $conn->query($sqlContarAsignados);

    $totalAsignados = 0;
    if ($resContarAsignados && $resContarAsignados->num_rows > 0) {
        $totalAsignados = (int)$resContarAsignados->fetch_assoc()['total'];
    }

    $cuposDisponibles = $capacidad - $totalAsignados;

    if ($cuposDisponibles <= 0) {
        continue;
    }

    /* -------------------------
       3.2 Obtener requisitos de la presentación
    -------------------------- */
    $sqlReq = "SELECT * FROM requisitos_presentacion
               WHERE id_presentacion = $id_presentacion
               LIMIT 1";
    $resReq = $conn->query($sqlReq);

    if (!$resReq || $resReq->num_rows == 0) {
        continue;
    }

    $req = $resReq->fetch_assoc();

    $nivel_minimo = (int)$req['nivel_minimo'];
    $rapidez_minima = $req['rapidez_minima'];
    $antiguedad_minima = $req['antiguedad_minima'];

    /* -------------------------
   3.3 Buscar candidatos elegibles
-------------------------- */

$nombresCategorias = [
    1 => 'Niños',
    2 => 'Juvenil',
    3 => 'Aspirantes',
    4 => 'Elenco Oficial'
];

$nombresNiveles = [
    1 => 'básico',
    2 => 'intermedio',
    3 => 'avanzado'
];

/* filtros comunes */
$filtrosComunes = "
    FROM integrantes i
    INNER JOIN criterios_integrante ci
        ON i.id_integrante = ci.id_integrante
    WHERE i.id_integrante NOT IN (
        SELECT ip.id_integrante
        FROM integrantes_presentacion ip
        WHERE ip.id_presentacion = $id_presentacion
    )
";

if (!is_null($rapidez_minima) && $rapidez_minima !== '') {
    $filtrosComunes .= " AND ci.rapidez_aprendizaje >= " . (int)$rapidez_minima;
}

if (!is_null($antiguedad_minima) && $antiguedad_minima !== '') {
    $filtrosComunes .= " AND ci.anios_antiguedad >= " . (int)$antiguedad_minima;
}

/* -----------------------------------------
   1) MISMA CATEGORÍA (todos los niveles)
----------------------------------------- */
$sqlMismaCategoria = "
    SELECT
        i.id_integrante,
        i.nombre,
        i.apellido,
        i.id_nivel,
        i.id_categoria,
        COALESCE(ci.rapidez_aprendizaje, 0) AS rapidez_aprendizaje,
        COALESCE(ci.anios_antiguedad, 0) AS anios_antiguedad,
        COALESCE(ci.cantidad_presentaciones, 0) AS cantidad_presentaciones,
        (
            SELECT COUNT(*)
            FROM detalle_asignacion da
            WHERE da.id_integrante = i.id_integrante
            AND da.estado_asignacion = 'En espera'
        ) AS veces_no_asignado
    $filtrosComunes
    AND i.id_categoria = $id_categoria
";

$resMismaCategoria = $conn->query($sqlMismaCategoria);

if (!$resMismaCategoria) {
    die("Error al obtener integrantes elegibles de la misma categoría: " . $conn->error);
}

$candidatosMismaCategoriaNivelOk = [];
$candidatosMismaCategoriaNivelInferior = [];

while ($i = $resMismaCategoria->fetch_assoc()) {
     $evaluacionSaturacion = evaluarSaturacionDia($conn, (int)$i['id_integrante'], $id_presentacion);

    if (!$evaluacionSaturacion['permitir']) {
       $alertasSaturacion[] = [
        'id_presentacion' => $id_presentacion,
        'presentacion' => $p['nombre_presentacion'] ?? ('Presentación #' . $id_presentacion),
        'integrante' => trim(($i['nombre'] ?? '') . ' ' . ($i['apellido'] ?? '')),
        'motivo' => $evaluacionSaturacion['motivo']
        ];
        continue;
    }

    $idNivelCandidato = (int)$i['id_nivel'];

    if ($idNivelCandidato >= $nivel_minimo) {
        $i['tipo_asignacion'] = 'normal';
        $i['motivo_excepcion'] = null;
        $i['prioridad_origen'] = 1;
        $candidatosMismaCategoriaNivelOk[] = $i;
    } else {
        $i['tipo_asignacion'] = 'mismo_categoria_nivel_inferior';
        $i['motivo_excepcion'] =
            "No hubo suficientes integrantes de la categoría " . ($nombresCategorias[$id_categoria] ?? 'requerida') .
            " con nivel mínimo " . ($nombresNiveles[$nivel_minimo] ?? 'requerido') .
            "; se asignó un integrante de la misma categoría con nivel " . ($nombresNiveles[$idNivelCandidato] ?? 'inferior') . ".";
        $i['prioridad_origen'] = 2;
        $candidatosMismaCategoriaNivelInferior[] = $i;
    }
}

/* -----------------------------------------
   Construir base de candidatos por etapas
----------------------------------------- */
$candidatosBase = [];

/* primero: misma categoría con nivel suficiente */
foreach ($candidatosMismaCategoriaNivelOk as $cand) {
    $candidatosBase[] = $cand;
}

/* segundo: misma categoría con nivel inferior,
   solo si todavía faltan candidatos */
if (count($candidatosBase) < $cuposDisponibles) {
    foreach ($candidatosMismaCategoriaNivelInferior as $cand) {
        $candidatosBase[] = $cand;
    }
}

/* -----------------------------------------
   3) APOYO EXTERNO
   Solo si ya no alcanza la misma categoría
----------------------------------------- */
if (count($candidatosBase) < $cuposDisponibles) {

    $condicionExterna = "";
    $orderExterno = "";

    if ($id_categoria == 3) {
        /* Aspirantes:
           1. Elenco Oficial cualquier nivel
           2. Juvenil solo avanzado
        */
        $condicionExterna = "
            (
                (i.id_categoria = 4)
                OR
                (i.id_categoria = 2 AND i.id_nivel = 3)
            )
        ";

        $orderExterno = "
            CASE
                WHEN i.id_categoria = 4 THEN 1
                WHEN i.id_categoria = 2 AND i.id_nivel = 3 THEN 2
                ELSE 99
            END,
            i.id_nivel DESC
        ";

    } elseif ($id_categoria == 4) {
        /* Elenco Oficial:
           1. Aspirante avanzado
        */
        $condicionExterna = "
            (
                i.id_categoria = 3 AND i.id_nivel = 3
            )
        ";

        $orderExterno = "
            CASE
                WHEN i.id_categoria = 3 AND i.id_nivel = 3 THEN 1
                ELSE 99
            END,
            i.id_nivel DESC
        ";

    } elseif ($id_categoria == 2) {
        /* Juvenil:
           1. Elenco Oficial cualquier nivel
           2. Aspirantes cualquier nivel
           3. Niños no
        */
        $condicionExterna = "
            (
                (i.id_categoria = 4)
                OR
                (i.id_categoria = 3)
            )
        ";

        $orderExterno = "
            CASE
                WHEN i.id_categoria = 4 THEN 1
                WHEN i.id_categoria = 3 THEN 2
                ELSE 99
            END,
            i.id_nivel DESC
        ";

    } elseif ($id_categoria == 1) {
        /* Niños:
           solo niños, no hay apoyo externo
        */
        $condicionExterna = "";
    }

    if ($condicionExterna !== "") {

        $sqlExternos = "
            SELECT
                i.id_integrante,
                i.nombre,
                i.apellido,
                i.id_nivel,
                i.id_categoria,
                COALESCE(ci.rapidez_aprendizaje, 0) AS rapidez_aprendizaje,
                COALESCE(ci.anios_antiguedad, 0) AS anios_antiguedad,
                COALESCE(ci.cantidad_presentaciones, 0) AS cantidad_presentaciones,
                (
                    SELECT COUNT(*)
                    FROM detalle_asignacion da
                    WHERE da.id_integrante = i.id_integrante
                    AND da.estado_asignacion = 'En espera'
                ) AS veces_no_asignado
            $filtrosComunes
            AND $condicionExterna
            ORDER BY $orderExterno
        ";

        $resExternos = $conn->query($sqlExternos);

        if (!$resExternos) {
            die("Error al obtener integrantes de apoyo externo: " . $conn->error);
        }

        while ($i = $resExternos->fetch_assoc()) {

    $evaluacionSaturacion = evaluarSaturacionDia($conn, (int)$i['id_integrante'], $id_presentacion);

    if (!$evaluacionSaturacion['permitir']) {
        $alertasSaturacion[] = [
         'id_presentacion' => $id_presentacion,
         'presentacion' => $p['nombre_presentacion'] ?? ('Presentación #' . $id_presentacion),
         'integrante' => trim(($i['nombre'] ?? '') . ' ' . ($i['apellido'] ?? '')),
         'motivo' => $evaluacionSaturacion['motivo']
        ];
        continue;
    }

    $idCategoriaCandidato = (int)$i['id_categoria'];
    $idNivelCandidato = (int)$i['id_nivel'];
            $i['tipo_asignacion'] = 'apoyo_categoria_externa';
            $i['motivo_excepcion'] =
                "No hubo suficientes integrantes de la categoría " . ($nombresCategorias[$id_categoria] ?? 'requerida') .
                ", se asignó " . strtolower($nombresCategorias[$idCategoriaCandidato] ?? 'categoría externa') .
                " " . ($nombresNiveles[$idNivelCandidato] ?? 'disponible') .
                " como apoyo excepcional.";

            /* prioridad externa según orden que definiste */
            if ($id_categoria == 3) {
                $i['prioridad_origen'] = ($idCategoriaCandidato == 4) ? 3 : 4;
            } elseif ($id_categoria == 4) {
                $i['prioridad_origen'] = 3;
            } elseif ($id_categoria == 2) {
                $i['prioridad_origen'] = ($idCategoriaCandidato == 4) ? 3 : 4;
            } else {
                $i['prioridad_origen'] = 99;
            }

            $candidatosBase[] = $i;
        }
    }
}

if (count($candidatosBase) == 0) {
    continue;
}

$hayEquidad = false;
foreach ($candidatosBase as $cand) {
    if ((int)$cand['veces_no_asignado'] > 0) {
        $hayEquidad = true;
        break;
    }
}

$cantidadCandidatos = count($candidatosBase);

$escenario = obtenerEscenario($nivel_minimo, $cuposDisponibles, $cantidadCandidatos, $hayEquidad);

$candidatos = [];

    /* =========================
       4. CALCULAR PUNTAJES
    ========================= */
    foreach ($candidatosBase as $i) {

        $id_integrante = (int)$i['id_integrante'];
        $id_nivel = (int)$i['id_nivel'];
        $rapidez = (int)$i['rapidez_aprendizaje'];
        $antiguedad = (int)$i['anios_antiguedad'];
        $cantidad_presentaciones = (int)$i['cantidad_presentaciones'];
        $veces_no_asignado = (int)$i['veces_no_asignado'];

        $cumpleNivelSuperior = $id_nivel > $nivel_minimo ? 1 : 0;

        /* Normalización */
        $base_nivel = ($id_nivel == $nivel_minimo) ? 0.80 : 1.00;
        $base_rapidez = limitar($rapidez / 5);
        $base_antiguedad = limitar($antiguedad / 10);
        $base_equidad = limitar($veces_no_asignado / 4);
        $base_experiencia = limitar((100 - $cantidad_presentaciones) / 100);

        /* Aplicar pesos reales */
        $puntaje_nivel = round($base_nivel * $peso_nivel, 2);
        $puntaje_rapidez = round($base_rapidez * $peso_rapidez, 2);
        $puntaje_antiguedad = round($base_antiguedad * $peso_antiguedad, 2);
        $puntaje_equidad = round($base_equidad * $peso_equidad, 2);
        $puntaje_experiencia = round($base_experiencia * $peso_experiencia, 2);

        $puntaje_total = round(
            $puntaje_nivel +
            $puntaje_rapidez +
            $puntaje_antiguedad +
            $puntaje_equidad +
            $puntaje_experiencia,
            2
        );

        $candidatos[] = [
            'id_integrante' => $id_integrante,
            'id_categoria' => (int)$i['id_categoria'],
            'id_nivel' => (int)$i['id_nivel'],
            'puntaje_nivel' => $puntaje_nivel,
            'puntaje_rapidez' => $puntaje_rapidez,
            'puntaje_antiguedad' => $puntaje_antiguedad,
            'puntaje_equidad' => $puntaje_equidad,
            'puntaje_experiencia' => $puntaje_experiencia,
            'puntaje_total' => $puntaje_total,
            'veces_no_asignado' => $veces_no_asignado,
            'cumpleNivelSuperior' => $cumpleNivelSuperior,
            'prioridad_origen' => (int)$i['prioridad_origen'],
            'tipo_asignacion' => $i['tipo_asignacion'],
            'motivo_excepcion' => $i['motivo_excepcion']
        ];
         
    }

    /* =========================
       5. ORDENAR CANDIDATOS
    ========================= */
    usort($candidatos, function ($a, $b) {
    if ($a['prioridad_origen'] != $b['prioridad_origen']) {
        return $a['prioridad_origen'] <=> $b['prioridad_origen'];
    }

    if ($b['puntaje_total'] != $a['puntaje_total']) {
        return $b['puntaje_total'] <=> $a['puntaje_total'];
    }

    if ($b['puntaje_equidad'] != $a['puntaje_equidad']) {
        return $b['puntaje_equidad'] <=> $a['puntaje_equidad'];
    }

    if ($b['puntaje_nivel'] != $a['puntaje_nivel']) {
        return $b['puntaje_nivel'] <=> $a['puntaje_nivel'];
    }

    if ($b['puntaje_rapidez'] != $a['puntaje_rapidez']) {
        return $b['puntaje_rapidez'] <=> $a['puntaje_rapidez'];
    }

    if ($b['puntaje_antiguedad'] != $a['puntaje_antiguedad']) {
        return $b['puntaje_antiguedad'] <=> $a['puntaje_antiguedad'];
    }

    return $b['puntaje_experiencia'] <=> $a['puntaje_experiencia'];
});

    $puntajeCorte = null;
    if (count($candidatos) >= $cuposDisponibles && $cuposDisponibles > 0) {
        $puntajeCorte = $candidatos[$cuposDisponibles - 1]['puntaje_total'];
    }

    /* =========================
       6. ASIGNAR SEGÚN CUPOS
    ========================= */
    foreach ($candidatos as $index => $c) {

        $id_integrante = (int)$c['id_integrante'];
        $motivoDesempate = "";

        if ($puntajeCorte !== null && $c['puntaje_total'] == $puntajeCorte && $index >= $cuposDisponibles && $cuposDisponibles > 0) {
            $ultimoAsignado = $candidatos[$cuposDisponibles - 1];
            $motivoDesempate = obtenerMotivoDesempate($ultimoAsignado, $c);
        }

        if ($index < $cuposDisponibles) {
            $decision = 'Asignado';

            $motivo = construirMotivoSegunEscenario(
                $escenario,
                $decision,
                $c['cumpleNivelSuperior'],
                $c['puntaje_total'],
                $puntajeCorte,
                $motivoDesempate
            );

            $motivo .= "\n" . construirDetallePuntajes(
                $c['puntaje_nivel'],
                $c['puntaje_rapidez'],
                $c['puntaje_antiguedad'],
                $c['puntaje_equidad'],
                $c['puntaje_experiencia'],
                $c['puntaje_total']
            );

            $motivoSQL = textoSQL($conn, $motivo);
            list($tipo_asignacion, $motivo_excepcion) = generarTipoYMotivoExcepcion(
                $id_categoria,
                $nivel_minimo,
                (int)$c['id_categoria'],
                (int)$c['id_nivel']
            );

            $tipoAsignacionSQL = textoSQL($conn, $tipo_asignacion);
            $motivoExcepcionSQL = ($motivo_excepcion !== null)
                ? "'" . textoSQL($conn, $motivo_excepcion) . "'"
                : "NULL";

            /* Guardar en evaluacion_asignacion */
            $sqlEval = "INSERT INTO evaluacion_asignacion
                        (
                            id_presentacion,
                            id_integrante,
                            puntaje_nivel,
                            puntaje_rapidez,
                            puntaje_antiguedad,
                            puntaje_equidad,
                            puntaje_experiencia,
                            puntaje_total,
                            decision_final,
                            motivo,
                            fecha_evaluacion
                        )
                        VALUES
                        (
                            $id_presentacion,
                            $id_integrante,
                            {$c['puntaje_nivel']},
                            {$c['puntaje_rapidez']},
                            {$c['puntaje_antiguedad']},
                            {$c['puntaje_equidad']},
                            {$c['puntaje_experiencia']},
                            {$c['puntaje_total']},
                            '$decision',
                            '$motivoSQL',
                            NOW()
                        )";

            if (!$conn->query($sqlEval)) {
                die("Error al guardar evaluación: " . $conn->error);
            }

            /* Guardar en cola_asignacion */
            $sqlCola = "INSERT INTO cola_asignacion
                        (
                            id_integrante,
                            id_presentacion,
                            fecha_ingreso,
                            estado,
                            prioridad,
                            prioridad_calculada,
                            observacion,
                            veces_no_asignado
                        )
                        VALUES
                        (
                            $id_integrante,
                            $id_presentacion,
                            NOW(),
                            'asignado',
                            0,
                            {$c['puntaje_total']},
                            '$motivoSQL',
                            {$c['veces_no_asignado']}
                        )";

            $conn->query($sqlCola);

            /* Verificar si ya existe en la relación final */
            $sqlVerificar = "SELECT *
                             FROM integrantes_presentacion
                             WHERE id_integrante = $id_integrante
                             AND id_presentacion = $id_presentacion";
            $resVerificar = $conn->query($sqlVerificar);

            if ($resVerificar && $resVerificar->num_rows == 0) {

                $sqlIP = "INSERT INTO integrantes_presentacion (id_integrante, id_presentacion)
                          VALUES ($id_integrante, $id_presentacion)";
                $conn->query($sqlIP);

                /* Buscar si ya existe detalle previo */
                $sqlBuscarDetalle = "SELECT id_detalle, fecha_ingreso_cola
                                     FROM detalle_asignacion
                                     WHERE id_integrante = $id_integrante
                                     AND id_presentacion = $id_presentacion
                                     ORDER BY id_detalle DESC
                                     LIMIT 1";
                $resBuscarDetalle = $conn->query($sqlBuscarDetalle);

                $fecha_asignacion = date("Y-m-d H:i:s");

                $sqlOrden = "SELECT COUNT(*) AS total_orden
                             FROM detalle_asignacion
                             WHERE id_presentacion = $id_presentacion
                             AND estado_asignacion = 'Asignado'";
                $resOrden = $conn->query($sqlOrden);
                $orden_atencion = 1;

                if ($resOrden && $resOrden->num_rows > 0) {
                    $orden_atencion = ((int)$resOrden->fetch_assoc()['total_orden']) + 1;
                }

                if ($resBuscarDetalle && $resBuscarDetalle->num_rows > 0) {
                    $detalle = $resBuscarDetalle->fetch_assoc();
                    $id_detalle = (int)$detalle['id_detalle'];
                    $fecha_ingreso_cola = $detalle['fecha_ingreso_cola'];

                    $segundos_espera = strtotime($fecha_asignacion) - strtotime($fecha_ingreso_cola);
                    $tiempo_espera = floor($segundos_espera / 60);

                    if ($segundos_espera > 0 && $tiempo_espera == 0) {
                        $tiempo_espera = 1;
                    }

                    $tipo_asignacion = $c['tipo_asignacion'] ?? 'normal';
                    $motivo_excepcion = $c['motivo_excepcion'] ?? null;

                    $tipoAsignacionSQL = $conn->real_escape_string($tipo_asignacion);

                    if ($motivo_excepcion !== null && trim($motivo_excepcion) !== '') {
                        $motivoExcepcionSQL = "'" . $conn->real_escape_string($motivo_excepcion) . "'";
                    } else {
                        $motivoExcepcionSQL = "NULL";
                    }
                    $sqlActualizarDetalle = "UPDATE detalle_asignacion
                         SET fecha_asignacion = '$fecha_asignacion',
                             tiempo_espera = $tiempo_espera,
                             orden_atencion = $orden_atencion,
                             estado_asignacion = 'Asignado',
                             veces_no_asignado = {$c['veces_no_asignado']},
                             criterio_seleccion = '$motivoSQL',
                             tipo_asignacion = '$tipoAsignacionSQL',
                             motivo_excepcion = $motivoExcepcionSQL
                         WHERE id_detalle = $id_detalle";
                    $conn->query($sqlActualizarDetalle);

                } else {
                    $fecha_ingreso_cola = $fecha_asignacion;
                    $segundos_espera = strtotime($fecha_asignacion) - strtotime($fecha_ingreso_cola);
                    $tiempo_espera = floor($segundos_espera / 60);

                    if ($segundos_espera > 0 && $tiempo_espera == 0) {
                        $tiempo_espera = 1;
                    }

                    $sqlDetalle = "INSERT INTO detalle_asignacion
               (
                    id_integrante,
                    id_presentacion,
                    id_categoria,
                    fecha_ingreso_cola,
                    fecha_asignacion,
                    tiempo_espera,
                    orden_atencion,
                    estado_asignacion,
                    veces_no_asignado,
                    criterio_seleccion,
                    tipo_asignacion,
                    motivo_excepcion
               )
               VALUES
               (
                    $id_integrante,
                    $id_presentacion,
                    $id_categoria,
                    '$fecha_ingreso_cola',
                    '$fecha_asignacion',
                    $tiempo_espera,
                    $orden_atencion,
                    'Asignado',
                    {$c['veces_no_asignado']},
                    '$motivoSQL',
                    '$tipoAsignacionSQL',
                    $motivoExcepcionSQL
               )";
                    $conn->query($sqlDetalle);
                }

                $asignados++;
            }

        } else {
            $decision = 'En espera';

            $motivo = construirMotivoSegunEscenario(
                $escenario,
                $decision,
                $c['cumpleNivelSuperior'],
                $c['puntaje_total'],
                $puntajeCorte,
                $motivoDesempate
            );

            $motivo .= "\n" . construirDetallePuntajes(
                $c['puntaje_nivel'],
                $c['puntaje_rapidez'],
                $c['puntaje_antiguedad'],
                $c['puntaje_equidad'],
                $c['puntaje_experiencia'],
                $c['puntaje_total']
            );

            $motivoSQL = textoSQL($conn, $motivo);

            $sqlEval = "INSERT INTO evaluacion_asignacion
                        (
                            id_presentacion,
                            id_integrante,
                            puntaje_nivel,
                            puntaje_rapidez,
                            puntaje_antiguedad,
                            puntaje_equidad,
                            puntaje_experiencia,
                            puntaje_total,
                            decision_final,
                            motivo,
                            fecha_evaluacion
                        )
                        VALUES
                        (
                            $id_presentacion,
                            $id_integrante,
                            {$c['puntaje_nivel']},
                            {$c['puntaje_rapidez']},
                            {$c['puntaje_antiguedad']},
                            {$c['puntaje_equidad']},
                            {$c['puntaje_experiencia']},
                            {$c['puntaje_total']},
                            '$decision',
                            '$motivoSQL',
                            NOW()
                        )";

            if (!$conn->query($sqlEval)) {
                die("Error al guardar evaluación en espera: " . $conn->error);
            }

            $sqlCola = "INSERT INTO cola_asignacion
                        (
                            id_integrante,
                            id_presentacion,
                            fecha_ingreso,
                            estado,
                            prioridad,
                            prioridad_calculada,
                            observacion,
                            veces_no_asignado
                        )
                        VALUES
                        (
                            $id_integrante,
                            $id_presentacion,
                            NOW(),
                            'esperando',
                            0,
                            {$c['puntaje_total']},
                            '$motivoSQL',
                            {$c['veces_no_asignado']}
                        )";

            $conn->query($sqlCola);

            $sqlExisteEspera = "SELECT id_detalle
                                FROM detalle_asignacion
                                WHERE id_integrante = $id_integrante
                                AND id_presentacion = $id_presentacion
                                LIMIT 1";
            $resExisteEspera = $conn->query($sqlExisteEspera);

            if (!$resExisteEspera || $resExisteEspera->num_rows == 0) {
                $sqlDetalleEspera = "INSERT INTO detalle_asignacion
                     (
                        id_integrante,
                        id_presentacion,
                        id_categoria,
                        fecha_ingreso_cola,
                        fecha_asignacion,
                        tiempo_espera,
                        orden_atencion,
                        estado_asignacion,
                        veces_no_asignado,
                        criterio_seleccion,
                        tipo_asignacion,
                        motivo_excepcion
                     )
                     VALUES
                     (
                        $id_integrante,
                        $id_presentacion,
                        $id_categoria,
                        NOW(),
                        NULL,
                        NULL,
                        NULL,
                        'En espera',
                        {$c['veces_no_asignado']},
                        '$motivoSQL',
                        '$tipoAsignacionSQL',
                        $motivoExcepcionSQL
                     )";
                $conn->query($sqlDetalleEspera);
            }
        }
    }
    /* =========================
   7. GENERAR SUPLENTES
========================= */
generarSuplentesPresentacion(
    $conn,
    $id_presentacion,
    $id_categoria,
    $nivel_minimo,
    $rapidez_minima,
    $antiguedad_minima,
    $peso_nivel,
    $peso_rapidez,
    $peso_antiguedad,
    $peso_equidad,
    $peso_experiencia
);
}

echo json_encode([
    "ok" => true,
    "asignados" => $asignados,
    "presentaciones_completas" => $presentaciones_completas,
    "sin_candidatos" => $sin_candidatos,
    "alertas_saturacion" => $alertasSaturacion,
    "mensaje" => ($asignados > 0)
        ? "Asignación ejecutada correctamente."
        : "No se realizaron nuevas asignaciones. Las presentaciones ya están completas o no existen más integrantes disponibles."
], JSON_UNESCAPED_UNICODE);

$conn->close();
?>