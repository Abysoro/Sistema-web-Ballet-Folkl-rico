<?php
include "conexion.php";

header("Content-Type: application/json; charset=utf-8");

date_default_timezone_set("America/La_Paz");
mysqli_query($conn, "SET time_zone = '-04:00'");

$categoria = $_GET["categoria"] ?? "todas";

$whereCategoria = "";

if ($categoria !== "todas") {
    $categoriaEscapada = mysqli_real_escape_string($conn, $categoria);
    $whereCategoria = "WHERE c.nombre_categoria = '$categoriaEscapada'";
}

function formatearTiempoPDF($segundos) {
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
        $texto = $dias . " día(s)";
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
    i.id_integrante,
    i.nombre AS integrante,
    i.apellido,
    p.nombre_presentacion,
    c.nombre_categoria,
    p.fecha AS fecha_presentacion,
    p.estado AS estado_presentacion,
    d.fecha_ingreso_cola,
    d.fecha_asignacion,
    d.estado_asignacion,
    d.tipo_asignacion,
    d.motivo_excepcion,
    d.veces_no_asignado,

    CASE
        WHEN d.estado_asignacion = 'Asignado'
             AND d.fecha_ingreso_cola IS NOT NULL
             AND d.fecha_asignacion IS NOT NULL
        THEN TIMESTAMPDIFF(SECOND, d.fecha_ingreso_cola, d.fecha_asignacion)

        WHEN d.estado_asignacion = 'En espera'
             AND d.fecha_ingreso_cola IS NOT NULL
        THEN TIMESTAMPDIFF(SECOND, d.fecha_ingreso_cola, NOW())

        ELSE 0
    END AS segundos_espera

FROM detalle_asignacion d
INNER JOIN integrantes i
    ON d.id_integrante = i.id_integrante
INNER JOIN presentaciones p
    ON d.id_presentacion = p.id_presentacion
INNER JOIN categorias c
    ON d.id_categoria = c.id_categoria

$whereCategoria

ORDER BY
    FIELD(c.nombre_categoria, 'Niños', 'Juvenil', 'Juveniles', 'Aspirantes', 'Elenco Oficial'),
    c.nombre_categoria ASC,
    i.apellido ASC,
    i.nombre ASC,
    p.fecha DESC,
    p.hora_presentacion DESC,
    p.nombre_presentacion ASC,
    d.id_detalle DESC
";

$res = $conn->query($sql);

if (!$res) {
    echo json_encode([
        "ok" => false,
        "mensaje" => "Error al generar reporte: " . $conn->error
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$datos = [];
$resumen = [];

while ($row = $res->fetch_assoc()) {
    $idIntegrante = (int)$row["id_integrante"];
    $segundosEspera = (int)$row["segundos_espera"];
    $vecesNoAsignado = (int)$row["veces_no_asignado"];
    $estadoAsignacion = $row["estado_asignacion"];

    /* =========================
       DETALLE HISTÓRICO
    ========================= */
    $datos[] = [
        "integrante" => $row["integrante"],
        "apellido" => $row["apellido"],
        "presentacion" => $row["nombre_presentacion"],
        "categoria" => $row["nombre_categoria"],
        "fecha_presentacion" => $row["fecha_presentacion"],
        "estado_presentacion" => $row["estado_presentacion"],
        "ingreso_cola" => $row["fecha_ingreso_cola"],
        "fecha_asignacion" => $row["fecha_asignacion"] ?: "--",
        "tiempo_espera" => formatearTiempoPDF($segundosEspera),
        "estado_asignacion" => $estadoAsignacion,
        "tipo_asignacion" => $row["tipo_asignacion"] ?: "Normal",
        "motivo_excepcion" => $row["motivo_excepcion"] ?: "--",
        "veces_no_asignado" => $vecesNoAsignado
    ];

    /* =========================
       RESUMEN POR INTEGRANTE
    ========================= */
    if (!isset($resumen[$idIntegrante])) {
        $resumen[$idIntegrante] = [
            "id_integrante" => $idIntegrante,
            "integrante" => $row["integrante"],
            "apellido" => $row["apellido"],
            "categoria" => $row["nombre_categoria"],

            "veces_no_elegido" => 0,
            "tiempo_total_segundos" => 0,
            "tiempo_total_espera" => "0 seg",

            "veces_asignado" => 0,
            "estado_posterior" => "No fue asignado posteriormente",
            "asignado_despues_de" => "No fue asignado posteriormente",
            "ultima_asignacion" => "--",

            "mayor_veces_no_asignado_antes_de_asignacion" => 0
        ];
    }

    $resumen[$idIntegrante]["veces_no_elegido"] += $vecesNoAsignado;
    $resumen[$idIntegrante]["tiempo_total_segundos"] += $segundosEspera;

    if ($estadoAsignacion === "Asignado") {
        $resumen[$idIntegrante]["veces_asignado"]++;
        $resumen[$idIntegrante]["estado_posterior"] = "Sí fue asignado posteriormente";

        /*
          Como el ORDER BY está por fecha descendente,
          la primera asignación encontrada será la más reciente.
        */
        if ($resumen[$idIntegrante]["ultima_asignacion"] === "--") {
            $resumen[$idIntegrante]["ultima_asignacion"] = $row["nombre_presentacion"];
        }

        if ($vecesNoAsignado > $resumen[$idIntegrante]["mayor_veces_no_asignado_antes_de_asignacion"]) {
            $resumen[$idIntegrante]["mayor_veces_no_asignado_antes_de_asignacion"] = $vecesNoAsignado;
        }
    }
}

/* =========================
   FORMATEAR RESUMEN FINAL
========================= */
$resumenFinal = [];

foreach ($resumen as $item) {
    $item["tiempo_total_espera"] = formatearTiempoPDF($item["tiempo_total_segundos"]);

    if ($item["veces_asignado"] > 0) {
        $vecesAntes = (int)$item["mayor_veces_no_asignado_antes_de_asignacion"];
        $item["asignado_despues_de"] = $vecesAntes . " vez/veces no elegido";
    }

    unset($item["tiempo_total_segundos"]);
    unset($item["mayor_veces_no_asignado_antes_de_asignacion"]);

    $resumenFinal[] = $item;
}

echo json_encode([
    "ok" => true,
    "categoria" => $categoria,
    "resumen" => $resumenFinal,
    "datos" => $datos
], JSON_UNESCAPED_UNICODE);

$conn->close();
?>