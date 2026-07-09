<?php
include "conexion.php";

header("Content-Type: application/json; charset=utf-8");

mysqli_set_charset($conn, "utf8mb4");

/* Evita que PHP imprima errores HTML y rompa el JSON */
mysqli_report(MYSQLI_REPORT_OFF);

function obtenerValor($conn, $sql, $campo) {
    $res = mysqli_query($conn, $sql);

    if (!$res) {
        return 0;
    }

    $fila = mysqli_fetch_assoc($res);
    return isset($fila[$campo]) ? (int)$fila[$campo] : 0;
}

/* =========================
   TARJETAS PRINCIPALES
========================= */

$totalIntegrantes = obtenerValor($conn, "
    SELECT COUNT(*) AS total
    FROM integrantes
    WHERE LOWER(estado) = 'activo'
", "total");

$totalPresentaciones = obtenerValor($conn, "
    SELECT COUNT(*) AS total
    FROM presentaciones
    WHERE fecha >= CURDATE()
", "total");

$totalAsignados = obtenerValor($conn, "
    SELECT COUNT(DISTINCT id_integrante) AS total
    FROM detalle_asignacion
    WHERE estado_asignacion = 'Asignado'
", "total");

$totalEspera = obtenerValor($conn, "
    SELECT COUNT(DISTINCT id_integrante) AS total
    FROM detalle_asignacion
    WHERE estado_asignacion = 'En espera'
", "total");

/* =========================
   PRÓXIMA PRESENTACIÓN
========================= */

$sqlProxima = "
    SELECT
        p.id_presentacion,
        p.nombre_presentacion,
        p.fecha,
        p.lugar,
        p.capacidad,
        COALESCE(c.nombre_categoria, 'Sin categoría') AS nombre_categoria,
        COALESCE(a.total_asignados, 0) AS total_asignados
    FROM presentaciones p
    LEFT JOIN categorias c ON p.id_categoria = c.id_categoria
    LEFT JOIN (
        SELECT
            id_presentacion,
            COUNT(DISTINCT id_integrante) AS total_asignados
        FROM detalle_asignacion
        WHERE estado_asignacion = 'Asignado'
        GROUP BY id_presentacion
    ) a ON p.id_presentacion = a.id_presentacion
    WHERE p.fecha >= CURDATE()
    ORDER BY p.fecha ASC
    LIMIT 1
";

$resProxima = mysqli_query($conn, $sqlProxima);

$proxima = [
    "nombre" => "Sin programar",
    "fecha" => "--",
    "lugar" => "--",
    "categoria" => "--",
    "cupos_disponibles" => 0,
    "ocupacion" => "--"
];

if ($resProxima && mysqli_num_rows($resProxima) > 0) {
    $fila = mysqli_fetch_assoc($resProxima);

    $capacidad = (int)($fila["capacidad"] ?? 0);
    $asignados = (int)($fila["total_asignados"] ?? 0);
    $cupos = max($capacidad - $asignados, 0);

    $porcentaje = 0;

    if ($capacidad > 0) {
        $porcentaje = ($asignados / $capacidad) * 100;
    }

    if ($porcentaje >= 80) {
        $ocupacion = "Alta";
    } elseif ($porcentaje >= 50) {
        $ocupacion = "Media";
    } else {
        $ocupacion = "Baja";
    }

    $proxima = [
        "nombre" => $fila["nombre_presentacion"] ?? "--",
        "fecha" => $fila["fecha"] ?? "--",
        "lugar" => $fila["lugar"] ?? "--",
        "categoria" => $fila["nombre_categoria"] ?? "--",
        "cupos_disponibles" => $cupos,
        "ocupacion" => $ocupacion
    ];
}

/* =========================
   AVISOS
========================= */

$sinAsignar = $totalEspera;

$presentacionesLimitadas = obtenerValor($conn, "
    SELECT COUNT(*) AS total
    FROM (
        SELECT
            p.id_presentacion,
            p.capacidad,
            COALESCE(COUNT(DISTINCT da.id_integrante), 0) AS asignados,
            (p.capacidad - COALESCE(COUNT(DISTINCT da.id_integrante), 0)) AS cupos
        FROM presentaciones p
        LEFT JOIN detalle_asignacion da
            ON p.id_presentacion = da.id_presentacion
            AND da.estado_asignacion = 'Asignado'
        WHERE p.fecha >= CURDATE()
        GROUP BY p.id_presentacion, p.capacidad
        HAVING cupos <= 2
    ) t
", "total");

/* Categoría con mayor demanda */
$sqlCategoriaDemanda = "
    SELECT
        c.nombre_categoria,
        COUNT(*) AS total
    FROM detalle_asignacion da
    INNER JOIN presentaciones p ON da.id_presentacion = p.id_presentacion
    LEFT JOIN categorias c ON p.id_categoria = c.id_categoria
    WHERE da.estado_asignacion = 'En espera'
    GROUP BY c.nombre_categoria
    ORDER BY total DESC
    LIMIT 1
";

$resCategoria = mysqli_query($conn, $sqlCategoriaDemanda);

$categoriaDemanda = "Sin datos";

if ($resCategoria && mysqli_num_rows($resCategoria) > 0) {
    $filaCategoria = mysqli_fetch_assoc($resCategoria);
    $categoriaDemanda = $filaCategoria["nombre_categoria"] ?? "Sin datos";
}

echo json_encode([
    "ok" => true,
    "resumen" => [
        "total_integrantes" => $totalIntegrantes,
        "total_presentaciones" => $totalPresentaciones,
        "total_asignados" => $totalAsignados,
        "total_espera" => $totalEspera
    ],
    "proxima" => $proxima,
    "avisos" => [
        "sin_asignar" => $sinAsignar,
        "presentaciones_limitadas" => $presentacionesLimitadas,
        "categoria_demanda" => $categoriaDemanda
    ]
], JSON_UNESCAPED_UNICODE);

mysqli_close($conn);
?>