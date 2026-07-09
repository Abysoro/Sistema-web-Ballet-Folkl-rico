<?php
include "conexion.php";

header("Content-Type: application/json; charset=utf-8");

function minutosEntre($inicio, $fin) {
    if (!$inicio || !$fin) return 0;

    $t1 = strtotime($inicio);
    $t2 = strtotime($fin);

    if (!$t1 || !$t2 || $t2 < $t1) return 0;

    return floor(($t2 - $t1) / 60);
}

function formatearMinutos($minutos) {
    $minutos = (int)$minutos;

    if ($minutos <= 0) {
        return "0 min";
    }

    $dias = floor($minutos / 1440);
    $horas = floor(($minutos % 1440) / 60);
    $mins = $minutos % 60;

    $partes = [];

    if ($dias > 0) $partes[] = $dias . " día(s)";
    if ($horas > 0) $partes[] = $horas . " h";
    if ($mins > 0) $partes[] = $mins . " min";

    return implode(" ", $partes);
}

$sql = "
SELECT
    da.id_detalle,
    da.id_integrante,
    i.nombre,
    i.apellido,
    c.nombre_categoria,
    p.nombre_presentacion,
    p.fecha AS fecha_presentacion,
    p.hora_presentacion,
    p.estado AS estado_presentacion,
    da.fecha_ingreso_cola,
    da.fecha_asignacion,
    da.estado_asignacion,
    da.tipo_asignacion,
    da.motivo_excepcion,
    da.veces_no_asignado
FROM detalle_asignacion da
INNER JOIN integrantes i
    ON da.id_integrante = i.id_integrante
INNER JOIN presentaciones p
    ON da.id_presentacion = p.id_presentacion
LEFT JOIN categorias c
    ON i.id_categoria = c.id_categoria
ORDER BY i.id_integrante ASC, da.fecha_ingreso_cola ASC, da.id_detalle ASC
";

$res = $conn->query($sql);

if (!$res) {
    echo json_encode([
        "ok" => false,
        "mensaje" => "Error al obtener historial de espera: " . $conn->error
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$integrantes = [];

while ($row = $res->fetch_assoc()) {
    $id = (int)$row["id_integrante"];

    if (!isset($integrantes[$id])) {
        $integrantes[$id] = [
            "id_integrante" => $id,
            "nombre_completo" => trim($row["nombre"] . " " . $row["apellido"]),
            "categoria" => $row["nombre_categoria"] ?? "Sin categoría",
            "veces_no_elegido" => 0,
            "veces_asignado" => 0,
            "tiempo_total_minutos" => 0,
            "tiempo_total_texto" => "0 min",
            "ultima_asignacion" => "--",
            "asignado_despues_de" => "--",
            "estado_final" => "Sin asignación posterior",
            "detalle" => [],
            "contador_esperas_consecutivas" => 0
        ];
    }

    $estado = $row["estado_asignacion"];
    $inicio = $row["fecha_ingreso_cola"];
    $fin = null;

    if ($estado === "Asignado" && !empty($row["fecha_asignacion"])) {
        $fin = $row["fecha_asignacion"];
    } else {
        /*
          Si no fue asignado y la presentación ya pasó,
          el tiempo se calcula hasta la fecha/hora de la presentación.
          Así no sigue creciendo eternamente.
        */
        if (!empty($row["fecha_presentacion"])) {
            $hora = !empty($row["hora_presentacion"]) ? $row["hora_presentacion"] : "23:59:59";
            $fin = $row["fecha_presentacion"] . " " . $hora;
        } else {
            $fin = date("Y-m-d H:i:s");
        }
    }

    $minutosEspera = minutosEntre($inicio, $fin);

    if ($estado === "En espera" || $estado === "Expirado") {
        $integrantes[$id]["veces_no_elegido"]++;
        $integrantes[$id]["contador_esperas_consecutivas"]++;
    }

    if ($estado === "Asignado") {
        $integrantes[$id]["veces_asignado"]++;
        $integrantes[$id]["ultima_asignacion"] = $row["nombre_presentacion"];
        $integrantes[$id]["asignado_despues_de"] = $integrantes[$id]["contador_esperas_consecutivas"] . " vez/veces no elegido";
        $integrantes[$id]["estado_final"] = "Sí fue asignado posteriormente";
        $integrantes[$id]["contador_esperas_consecutivas"] = 0;
    }

    $integrantes[$id]["tiempo_total_minutos"] += $minutosEspera;

    $integrantes[$id]["detalle"][] = [
        "presentacion" => $row["nombre_presentacion"],
        "fecha_presentacion" => $row["fecha_presentacion"],
        "estado" => $estado,
        "ingreso_cola" => $inicio,
        "fecha_asignacion" => $row["fecha_asignacion"] ?: "--",
        "tiempo_espera" => formatearMinutos($minutosEspera),
        "tipo_asignacion" => $row["tipo_asignacion"] ?: "Normal",
        "motivo_excepcion" => $row["motivo_excepcion"] ?: "--"
    ];
}

$datos = [];

foreach ($integrantes as $item) {
    unset($item["contador_esperas_consecutivas"]);

    $item["tiempo_total_texto"] = formatearMinutos($item["tiempo_total_minutos"]);

    if ($item["veces_asignado"] == 0 && $item["veces_no_elegido"] > 0) {
        $item["asignado_despues_de"] = "No fue asignado posteriormente";
    }

    $datos[] = $item;
}

echo json_encode([
    "ok" => true,
    "datos" => $datos
], JSON_UNESCAPED_UNICODE);

$conn->close();
?>