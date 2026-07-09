<?php
date_default_timezone_set('America/La_Paz');

function obtenerJornadaPorId($conn, $id_jornada) {
    $id_jornada = (int)$id_jornada;

    $sql = "SELECT id_jornada, nombre_jornada, fecha, lugar, hora_inicio, estado
            FROM jornadas
            WHERE id_jornada = $id_jornada
            LIMIT 1";

    $res = $conn->query($sql);

    if (!$res || $res->num_rows === 0) {
        return null;
    }

    return $res->fetch_assoc();
}

function obtenerPresentacionesDeJornada($conn, $id_jornada) {
    $id_jornada = (int)$id_jornada;

    $sql = "SELECT 
                p.id_presentacion,
                p.nombre_presentacion,
                p.fecha,
                p.lugar,
                p.capacidad,
                p.duracion_minutos,
                p.estado,
                p.id_categoria,
                c.nombre_categoria,
                COUNT(ip.id_integrante) AS integrantes_asignados
            FROM presentaciones p
            INNER JOIN categorias c 
                ON p.id_categoria = c.id_categoria
            LEFT JOIN integrantes_presentacion ip 
                ON p.id_presentacion = ip.id_presentacion
            WHERE p.id_jornada = $id_jornada
            GROUP BY 
                p.id_presentacion, p.nombre_presentacion, p.fecha, p.lugar,
                p.capacidad, p.duracion_minutos, p.estado, p.id_categoria, c.nombre_categoria
            ORDER BY p.id_presentacion ASC";

    $res = $conn->query($sql);

    if (!$res) {
        throw new Exception("Error al obtener presentaciones: " . $conn->error);
    }

    $presentaciones = [];

    while ($row = $res->fetch_assoc()) {
        $id_presentacion = (int)$row['id_presentacion'];
        $row['id_presentacion'] = $id_presentacion;
        $row['capacidad'] = (int)$row['capacidad'];
        $row['duracion_minutos'] = (int)$row['duracion_minutos'];
        $row['integrantes_asignados'] = (int)$row['integrantes_asignados'];
        $row['integrantes'] = obtenerIntegrantesPorPresentacion($conn, $id_presentacion);

        $presentaciones[$id_presentacion] = $row;
    }

    return $presentaciones;
}

function obtenerIntegrantesPorPresentacion($conn, $id_presentacion) {
    $id_presentacion = (int)$id_presentacion;

    $sql = "SELECT 
                i.id_integrante,
                i.nombre,
                i.apellido,
                i.id_categoria,
                c.nombre_categoria
            FROM integrantes_presentacion ip
            INNER JOIN integrantes i 
                ON ip.id_integrante = i.id_integrante
            INNER JOIN categorias c 
                ON i.id_categoria = c.id_categoria
            WHERE ip.id_presentacion = $id_presentacion
            ORDER BY i.nombre, i.apellido";

    $res = $conn->query($sql);

    if (!$res) {
        throw new Exception("Error al obtener integrantes por presentación: " . $conn->error);
    }

    $integrantes = [];

    while ($row = $res->fetch_assoc()) {
        $id_integrante = (int)$row['id_integrante'];

        $categoria = mb_strtolower(trim($row['nombre_categoria']), 'UTF-8');
        $minutos_requeridos = ($categoria === 'niños' || $categoria === 'ninos') ? 7 : 5;

        $integrantes[$id_integrante] = [
            'id_integrante' => $id_integrante,
            'nombre_completo' => trim($row['nombre'] . ' ' . $row['apellido']),
            'nombre_categoria' => $row['nombre_categoria'],
            'minutos_requeridos' => $minutos_requeridos
        ];
    }

    return $integrantes;
}

function calcularMatrizConflictos($presentaciones) {
    $ids = array_keys($presentaciones);
    $matriz = [];

    foreach ($ids as $idA) {
        $matriz[$idA] = [];
        foreach ($ids as $idB) {
            if ($idA === $idB) {
                $matriz[$idA][$idB] = 0;
                continue;
            }

            $integrantesA = array_keys($presentaciones[$idA]['integrantes']);
            $integrantesB = array_keys($presentaciones[$idB]['integrantes']);

            $repetidos = array_intersect($integrantesA, $integrantesB);
            $matriz[$idA][$idB] = count($repetidos);
        }
    }

    return $matriz;
}

function calcularConflictoTotalPorPresentacion($matriz) {
    $totales = [];

    foreach ($matriz as $id_presentacion => $fila) {
        $totales[$id_presentacion] = array_sum($fila);
    }

    return $totales;
}

function construirOrdenGreedyInteligente($presentaciones, $matrizCosto) {
    $ids = array_keys($presentaciones);

    if (count($ids) <= 1) {
        return $ids;
    }

    $mejorOrden = [];
    $mejorCostoTotal = PHP_INT_MAX;

    // probar cada presentación como inicio
    foreach ($ids as $inicio) {
        $orden = [$inicio];
        $usados = [$inicio => true];

        while (count($orden) < count($presentaciones)) {
            $ultimo = end($orden);
            $mejor = null;
            $mejorCostoInmediato = PHP_INT_MAX;
            $mejorCostoFuturo = PHP_INT_MAX;

            foreach ($ids as $id_presentacion) {
                if (isset($usados[$id_presentacion])) {
                    continue;
                }

                $costoInmediato = $matrizCosto[$ultimo][$id_presentacion];
                $costoFuturo = array_sum($matrizCosto[$id_presentacion]);

                if (
                    $costoInmediato < $mejorCostoInmediato ||
                    ($costoInmediato === $mejorCostoInmediato && $costoFuturo < $mejorCostoFuturo) ||
                    ($costoInmediato === $mejorCostoInmediato && $costoFuturo === $mejorCostoFuturo && $id_presentacion < $mejor)
                ) {
                    $mejor = $id_presentacion;
                    $mejorCostoInmediato = $costoInmediato;
                    $mejorCostoFuturo = $costoFuturo;
                }
            }

            if ($mejor === null) {
                break;
            }

            $orden[] = $mejor;
            $usados[$mejor] = true;
        }

        $costoTotal = 0;
        for ($i = 1; $i < count($orden); $i++) {
            $costoTotal += $matrizCosto[$orden[$i - 1]][$orden[$i]];
        }

        if ($costoTotal < $mejorCostoTotal) {
            $mejorCostoTotal = $costoTotal;
            $mejorOrden = $orden;
        }
    }

    return $mejorOrden;
}

function obtenerMinutosCambioEntrePresentaciones($presentacionA, $presentacionB) {
    $integrantesA = $presentacionA['integrantes'] ?? [];
    $integrantesB = $presentacionB['integrantes'] ?? [];

    $repetidosIds = array_intersect(array_keys($integrantesA), array_keys($integrantesB));

    if (empty($repetidosIds)) {
        return 0;
    }

    $minutos_necesarios = 0;

    foreach ($repetidosIds as $id_integrante) {
        $minutosA = (int)($integrantesA[$id_integrante]['minutos_requeridos'] ?? 0);
        $minutosB = (int)($integrantesB[$id_integrante]['minutos_requeridos'] ?? 0);

        $minutos_necesarios = max($minutos_necesarios, $minutosA, $minutosB);
    }

    return $minutos_necesarios;
}

function generarHorasCronograma($presentaciones, $orden, $hora_inicio_jornada = null) {
    $cronograma = [];

    $hora_actual = $hora_inicio_jornada
        ? strtotime($hora_inicio_jornada)
        : strtotime("18:00:00");

    /*
      Regla:
      - Solo se pueden aplicar 2 ajustes automáticos.
      - Cada ajuste automático suma 5 minutos.
      - Si después de esos ajustes quedan choques, se mostrarán en conflictos detectados.
    */
    $bloqueCambioVestuario = 5;
    $maxAjustesAutomaticos = 2;
    $ajustesUsados = 0;

    foreach ($orden as $indice => $id_presentacion) {
        $pActual = $presentaciones[$id_presentacion];
        $duracion = (int)$pActual['duracion_minutos'];

        $hora_inicio_real = $hora_actual;
        $minutos_extra_vestuario = 0;
        $observacion = "Sin conflicto";

        /*
          Revisar si la presentación actual comparte integrantes
          con alguna presentación anterior ya programada.
        */
        $hayConflictoPrevio = false;

        foreach ($cronograma as $anterior) {
            $pAnterior = $presentaciones[$anterior['id_presentacion']];
            $minutosCambio = obtenerMinutosCambioEntrePresentaciones($pAnterior, $pActual);

            if ($minutosCambio <= 0) {
                continue;
            }

            $finAnterior = strtotime($anterior['hora_fin_estimada']);
            $minutosDisponibles = (int)(($hora_inicio_real - $finAnterior) / 60);

            if ($minutosDisponibles < $minutosCambio) {
                $hayConflictoPrevio = true;
                break;
            }
        }

        /*
          Si hay conflicto, solo se permite sumar 5 minutos
          máximo 2 veces en todo el cronograma.
        */
        if ($hayConflictoPrevio) {
            if ($ajustesUsados < $maxAjustesAutomaticos) {
                $hora_inicio_real = strtotime("+$bloqueCambioVestuario minutes", $hora_inicio_real);
                $minutos_extra_vestuario = $bloqueCambioVestuario;
                $ajustesUsados++;

                $observacion = "Se añadieron 5 min por cambio de vestuario";
            } else {
                $observacion = "Conflicto detectado: límite de 2 ajustes alcanzado";
            }
        }

        $hora_inicio = date("H:i:s", $hora_inicio_real);
        $hora_fin = date("H:i:s", strtotime("+$duracion minutes", $hora_inicio_real));

        $cronograma[] = [
            'orden_salida' => $indice + 1,
            'id_presentacion' => $id_presentacion,
            'nombre_presentacion' => $pActual['nombre_presentacion'],
            'duracion_minutos' => $duracion,
            'hora_inicio_estimada' => $hora_inicio,
            'hora_fin_estimada' => $hora_fin,
            'conflictos_previos' => 0,
            'minutos_extra_vestuario' => $minutos_extra_vestuario,
            'observacion' => $observacion
        ];

        $hora_actual = strtotime($hora_fin);
    }

    return $cronograma;
}

function calcularConflictosDetallados($presentaciones, $cronograma) {
    $conflictos = [];
    $posiciones = [];

    foreach ($cronograma as $item) {
        $posiciones[$item['id_presentacion']] = $item;
    }

    $ids = array_keys($presentaciones);

    for ($i = 0; $i < count($ids); $i++) {
        for ($j = $i + 1; $j < count($ids); $j++) {
            $idA = $ids[$i];
            $idB = $ids[$j];

            $integrantesA = $presentaciones[$idA]['integrantes'];
            $integrantesB = $presentaciones[$idB]['integrantes'];

            $repetidosIds = array_intersect(array_keys($integrantesA), array_keys($integrantesB));

            if (empty($repetidosIds)) {
                continue;
            }

            $itemA = $posiciones[$idA];
            $itemB = $posiciones[$idB];

            $inicioA = strtotime($itemA['hora_inicio_estimada']);
            $finA = strtotime($itemA['hora_fin_estimada']);
            $inicioB = strtotime($itemB['hora_inicio_estimada']);
            $finB = strtotime($itemB['hora_fin_estimada']);

            if ($inicioA <= $inicioB) {
                $primera = $itemA;
                $segunda = $itemB;
                $minutos_disponibles = (int)(($inicioB - $finA) / 60);
            } else {
                $primera = $itemB;
                $segunda = $itemA;
                $minutos_disponibles = (int)(($inicioA - $finB) / 60);
            }

            foreach ($repetidosIds as $id_integrante) {
                $integrante = $integrantesA[$id_integrante];
                $minutos_requeridos = (int)$integrante['minutos_requeridos'];

                if ($minutos_disponibles < $minutos_requeridos) {
                    $conflictos[] = [
                        'id_integrante' => $id_integrante,
                        'integrante' => $integrante['nombre_completo'],
                        'presentacion_1' => $primera['nombre_presentacion'],
                        'presentacion_2' => $segunda['nombre_presentacion'],
                        'id_presentacion_1' => $primera['id_presentacion'],
                        'id_presentacion_2' => $segunda['id_presentacion'],
                        'minutos_requeridos' => $minutos_requeridos,
                        'minutos_disponibles' => max($minutos_disponibles, 0),
                        'estado' => 'Conflicto'
                    ];
                }
            }
        }
    }

    return $conflictos;
}

function agregarResumenConflictosAlCronograma(&$cronograma, $conflictos) {
    $contador = [];

    foreach ($cronograma as &$item) {
        $item['conflictos_previos'] = 0;

        if (!isset($item['observacion']) || trim($item['observacion']) === '') {
            $item['observacion'] = 'Sin conflicto';
        }
    }
    unset($item);

    foreach ($conflictos as $conflicto) {
        $id1 = $conflicto['id_presentacion_1'];
        $id2 = $conflicto['id_presentacion_2'];

        if (!isset($contador[$id1])) $contador[$id1] = 0;
        if (!isset($contador[$id2])) $contador[$id2] = 0;

        $contador[$id1]++;
        $contador[$id2]++;
    }

    foreach ($cronograma as &$item) {
        $id = $item['id_presentacion'];

        if (isset($contador[$id]) && $contador[$id] > 0) {
            $item['conflictos_previos'] = $contador[$id];
            $item['observacion'] = 'Conflicto de vestuario no resuelto';
        }
    }
    unset($item);
}

function optimizarCronogramaCompleto($conn, $id_jornada) {
    $jornada = obtenerJornadaPorId($conn, $id_jornada);

    if (!$jornada) {
        throw new Exception("La jornada no existe.");
    }

    $presentaciones = obtenerPresentacionesDeJornada($conn, $id_jornada);

    if (empty($presentaciones)) {
        return [
            'jornada' => $jornada,
            'presentaciones' => [],
            'cronograma' => [],
            'conflictos' => []
        ];
    }

    $matriz = calcularMatrizConflictos($presentaciones);
    $matrizCosto = calcularMatrizCostoCambio($presentaciones);
    $orden = construirOrdenGreedyInteligente($presentaciones, $matrizCosto);
    $cronograma = generarHorasCronograma($presentaciones, $orden, $jornada['hora_inicio']);
    $conflictos = calcularConflictosDetallados($presentaciones, $cronograma);

    agregarResumenConflictosAlCronograma($cronograma, $conflictos);
    $resumen = generarResumenCronograma($cronograma, $conflictos);


    return [
        'jornada' => $jornada,
        'presentaciones' => array_values($presentaciones),
        'cronograma' => $cronograma,
        'conflictos' => $conflictos,
        'resumen' => $resumen
        ];
}

function calcularCostoCambioEntrePresentaciones($presentacionA, $presentacionB) {
    $integrantesA = $presentacionA['integrantes'] ?? [];
    $integrantesB = $presentacionB['integrantes'] ?? [];

    $repetidosIds = array_intersect(array_keys($integrantesA), array_keys($integrantesB));

    if (empty($repetidosIds)) {
        return 0;
    }

    $costo = 0;

    foreach ($repetidosIds as $id_integrante) {
        $minA = (int)($integrantesA[$id_integrante]['minutos_requeridos'] ?? 0);
        $minB = (int)($integrantesB[$id_integrante]['minutos_requeridos'] ?? 0);
        $costo = max($costo, $minA, $minB);
    }

    return $costo;
}

function calcularMatrizCostoCambio($presentaciones) {
    $ids = array_keys($presentaciones);
    $matriz = [];

    foreach ($ids as $idA) {
        $matriz[$idA] = [];

        foreach ($ids as $idB) {
            if ($idA === $idB) {
                $matriz[$idA][$idB] = 0;
                continue;
            }

            $matriz[$idA][$idB] = calcularCostoCambioEntrePresentaciones(
                $presentaciones[$idA],
                $presentaciones[$idB]
            );
        }
    }

    return $matriz;
}

function generarResumenCronograma($cronograma, $conflictos) {
    $totalExtra = 0;
    $presentacionesAjustadas = 0;

    foreach ($cronograma as $item) {
        $extra = (int)($item['minutos_extra_vestuario'] ?? 0);
        $totalExtra += $extra;

        if ($extra > 0) {
            $presentacionesAjustadas++;
        }
    }

    if (count($conflictos) === 0 && $totalExtra === 0) {
        $estado = "Cronograma óptimo";
        $detalle = "No se requirieron ajustes adicionales.";
        $clase = "optimo";
    } elseif (count($conflictos) === 0 && $totalExtra > 0) {
        $estado = "Cronograma corregido y justo";
        $detalle = "El sistema reordenó y ajustó tiempos para evitar conflictos de vestuario.";
        $clase = "corregido";
    } else {
        $estado = "Cronograma con observaciones";
        $detalle = "Persisten conflictos que requieren revisión manual.";
        $clase = "observacion";
    }

    return [
        'estado' => $estado,
        'detalle' => $detalle,
        'clase' => $clase,
        'total_minutos_extra' => $totalExtra,
        'presentaciones_ajustadas' => $presentacionesAjustadas,
        'conflictos_restantes' => count($conflictos)
    ];
}
?>

