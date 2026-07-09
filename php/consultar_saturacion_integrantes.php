<?php
include "conexion.php";

header("Content-Type: application/json; charset=utf-8");

function horaAMinutosConsulta($hora) {
    if (!$hora) return null;

    $partes = explode(":", $hora);

    if (count($partes) < 2) return null;

    return ((int)$partes[0] * 60) + (int)$partes[1];
}

/* Obtener parámetros de saturación */
$sqlParam = "
SELECT *
FROM parametros_saturacion_dia
ORDER BY id_parametro DESC
LIMIT 1
";

$resParam = mysqli_query($conn, $sqlParam);

if ($resParam && mysqli_num_rows($resParam) > 0) {
    $param = mysqli_fetch_assoc($resParam);
} else {
    $param = [
        "max_jornadas_dia" => 2,
        "max_presentaciones_dia" => 4,
        "max_presentaciones_por_jornada" => 3,
        "max_minutos_danza_dia" => 60,
        "min_descanso_mismo_lugar" => 10,
        "buffer_traslado_min" => 15,
        "tiempo_cambio_fijo_min" => 10
    ];
}

/* Obtener integrantes activos */
$sqlIntegrantes = "
SELECT 
    i.id_integrante,
    i.nombre,
    i.apellido,
    c.nombre_categoria
FROM integrantes i
INNER JOIN categorias c 
    ON i.id_categoria = c.id_categoria
WHERE i.estado = 'activo'
ORDER BY c.nombre_categoria ASC, i.nombre ASC, i.apellido ASC
";

$resIntegrantes = mysqli_query($conn, $sqlIntegrantes);

if (!$resIntegrantes) {
    echo json_encode([
        "ok" => false,
        "mensaje" => "Error al obtener integrantes: " . mysqli_error($conn)
    ]);
    exit;
}

$resultado = [];

while ($integrante = mysqli_fetch_assoc($resIntegrantes)) {
    $id_integrante = (int)$integrante["id_integrante"];

    /*
      Solo consulta presentaciones activas y actuales/futuras.
      Las presentaciones inactivas quedan como historial, pero no saturan.
    */
    $sqlAsignaciones = "
    SELECT
        p.id_presentacion,
        p.nombre_presentacion,
        p.fecha,
        p.hora_presentacion,
        p.duracion_minutos,
        p.estado AS estado_presentacion,
        j.id_jornada,
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
      AND p.estado = 'Activa'
      AND p.fecha >= CURDATE()
      AND j.estado = 'Activo'
    ORDER BY p.fecha ASC, j.hora_inicio ASC, p.hora_presentacion ASC
    ";

    $resAsignaciones = mysqli_query($conn, $sqlAsignaciones);

    if (!$resAsignaciones) {
        echo json_encode([
            "ok" => false,
            "mensaje" => "Error al verificar saturación: " . mysqli_error($conn)
        ]);
        exit;
    }

    $asignaciones = [];

    while ($fila = mysqli_fetch_assoc($resAsignaciones)) {
        $asignaciones[] = $fila;
    }

    if (count($asignaciones) === 0) {
        $resultado[] = [
            "id_integrante" => $id_integrante,
            "nombre_completo" => $integrante["nombre"] . " " . $integrante["apellido"],
            "categoria" => $integrante["nombre_categoria"],
            "estado_saturacion" => "sin_asignaciones",
            "total_presentaciones" => 0,
            "total_jornadas" => 0,
            "minutos_totales" => 0,
            "motivo" => "No tiene asignaciones activas actualmente."
        ];
        continue;
    }

    /*
      Agrupar por fecha para revisar saturación por día.
    */
    $porFecha = [];

    foreach ($asignaciones as $asig) {
        $fecha = $asig["fecha"];

        if (!isset($porFecha[$fecha])) {
            $porFecha[$fecha] = [];
        }

        $porFecha[$fecha][] = $asig;
    }

    $estaSaturado = false;
    $motivosGenerales = [];
    $totalPresentacionesGlobal = 0;
    $totalJornadasGlobal = [];
    $minutosTotalesGlobal = 0;

    foreach ($porFecha as $fecha => $eventosDia) {
        $jornadasDia = [];
        $presentacionesPorJornada = [];
        $minutosTotalesDia = 0;
        $motivosDia = [];

        foreach ($eventosDia as $ev) {
            $jornadasDia[$ev["id_jornada"]] = true;

            if (!isset($presentacionesPorJornada[$ev["id_jornada"]])) {
                $presentacionesPorJornada[$ev["id_jornada"]] = 0;
            }

            $presentacionesPorJornada[$ev["id_jornada"]]++;
            $minutosTotalesDia += (int)$ev["duracion_minutos"];

            $totalPresentacionesGlobal++;
            $totalJornadasGlobal[$ev["id_jornada"]] = true;
            $minutosTotalesGlobal += (int)$ev["duracion_minutos"];
        }

        $cantidadJornadasDia = count($jornadasDia);
        $cantidadPresentacionesDia = count($eventosDia);
        $maxPorJornada = 0;

        foreach ($presentacionesPorJornada as $cantidad) {
            if ($cantidad > $maxPorJornada) {
                $maxPorJornada = $cantidad;
            }
        }

        $maxJornadasDia = (int)$param["max_jornadas_dia"];
        $maxPresentacionesDia = (int)$param["max_presentaciones_dia"];
        $maxPresentacionesPorJornada = (int)$param["max_presentaciones_por_jornada"];
        $maxMinutosDanzaDia = (int)$param["max_minutos_danza_dia"];

/*
  Cada condición trabaja de forma independiente.
  No necesita cumplir 3 jornadas para saturarse.
  Si alcanza 6 presentaciones en una sola jornada, igual se satura.
*/

        if ($cantidadPresentacionesDia >= $maxPresentacionesDia) {
            $motivosDia[] = "Alcanza el límite de $maxPresentacionesDia presentaciones activas el día $fecha.";
        }

        if ($cantidadJornadasDia >= $maxJornadasDia) {
            $motivosDia[] = "Alcanza el límite de $maxJornadasDia jornadas activas el día $fecha.";
        }

        if ($maxPorJornada > $maxPresentacionesPorJornada) {
            $motivosDia[] = "Supera el máximo de $maxPresentacionesPorJornada presentaciones dentro de una misma jornada el día $fecha.";
        }

        if ($minutosTotalesDia >= $maxMinutosDanzaDia) {
            $motivosDia[] = "Alcanza el límite de $maxMinutosDanzaDia minutos acumulados de danza el día $fecha.";
        }

        /*
          Revisar conflicto entre jornadas diferentes.
        */
        usort($eventosDia, function($a, $b) {
            return strcmp($a["hora_inicio"], $b["hora_inicio"]);
        });

        for ($i = 0; $i < count($eventosDia) - 1; $i++) {
            $actual = $eventosDia[$i];
            $siguiente = $eventosDia[$i + 1];

            if ((int)$actual["id_jornada"] === (int)$siguiente["id_jornada"]) {
                continue;
            }

            $finActual = horaAMinutosConsulta($actual["hora_fin"]);
            $inicioSiguiente = horaAMinutosConsulta($siguiente["hora_inicio"]);

            if ($finActual === null || $inicioSiguiente === null) {
                $motivosDia[] = "Falta hora de inicio o fin en alguna jornada del día $fecha.";
                continue;
            }

            $tiempoDisponible = $inicioSiguiente - $finActual;

            if (trim($actual["lugar"]) === trim($siguiente["lugar"])) {
                $tiempoNecesario = (int)$param["min_descanso_mismo_lugar"];
            } else {
                $tiempoNecesario =
                    (int)$param["buffer_traslado_min"] +
                    (int)$param["tiempo_cambio_fijo_min"];
            }

            if ($tiempoDisponible < $tiempoNecesario) {
                $motivosDia[] =
                    "No alcanza el tiempo entre la jornada '" .
                    $actual["nombre_jornada"] . "' (" . $actual["lugar"] .
                    ") y la jornada '" . $siguiente["nombre_jornada"] .
                    "' (" . $siguiente["lugar"] . ") el día $fecha.";
            }
        }

        if (count($motivosDia) > 0) {
            $estaSaturado = true;
            $motivosGenerales = array_merge($motivosGenerales, $motivosDia);
        }
    }
    /*
  Validación global de asignaciones activas.
  Esta regla debe coincidir con la asignación inteligente:
  si al agregar una nueva presentación alcanza el límite,
  ya se marca como no disponible para nueva asignación.
*/

$maxPresentacionesActivas = (int)$param["max_presentaciones_dia"];
$maxJornadasActivas = (int)$param["max_jornadas_dia"];
$maxMinutosActivos = (int)$param["max_minutos_danza_dia"];

$totalSiSeAsignaUnaMas = $totalPresentacionesGlobal + 1;

if ($maxPresentacionesActivas > 0 && $totalSiSeAsignaUnaMas >= $maxPresentacionesActivas) {
    $estaSaturado = true;
    $motivosGenerales[] = "No disponible para nueva asignación: actualmente tiene $totalPresentacionesGlobal presentaciones activas y con una nueva llegaría a $totalSiSeAsignaUnaMas, alcanzando el límite de $maxPresentacionesActivas.";
}

if ($maxJornadasActivas > 0 && count($totalJornadasGlobal) >= $maxJornadasActivas) {
    $estaSaturado = true;
    $motivosGenerales[] = "Alcanza el límite de $maxJornadasActivas jornadas activas.";
}

if ($maxMinutosActivos > 0 && $minutosTotalesGlobal >= $maxMinutosActivos) {
    $estaSaturado = true;
    $motivosGenerales[] = "Alcanza el límite de $maxMinutosActivos minutos acumulados de danza.";
}

$motivosGenerales = array_unique($motivosGenerales);


    $resultado[] = [
    "id_integrante" => $id_integrante,
    "nombre_completo" => $integrante["nombre"] . " " . $integrante["apellido"],
    "categoria" => $integrante["nombre_categoria"],
    "estado_saturacion" => $estaSaturado ? "saturado" : "normal",
    "total_presentaciones" => $totalPresentacionesGlobal,
    "total_jornadas" => count($totalJornadasGlobal),
    "minutos_totales" => $minutosTotalesGlobal,
    "motivo" => $estaSaturado
        ? implode(" ", $motivosGenerales)
        : "El integrante no presenta saturación con sus asignaciones activas."
];
}

echo json_encode([
    "ok" => true,
    "integrantes" => $resultado
], JSON_UNESCAPED_UNICODE);

mysqli_close($conn);
?>