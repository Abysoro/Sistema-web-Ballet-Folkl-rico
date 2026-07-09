<?php
include "conexion.php";

header('Content-Type: application/json; charset=utf-8');

$id_jornada = isset($_GET['id_jornada']) ? (int)$_GET['id_jornada'] : 0;

if ($id_jornada <= 0) {
    echo json_encode([
        "ok" => false,
        "mensaje" => "Jornada no válida."
    ]);
    exit;
}

$sqlCronograma = "SELECT 
                    cp.id_cronograma,
                    cp.orden_salida,
                    cp.hora_inicio_estimada,
                    cp.hora_fin_estimada,
                    cp.conflictos_previos,
                    cp.observacion,
                    p.id_presentacion,
                    p.nombre_presentacion
                  FROM cronograma_presentacion cp
                  INNER JOIN presentaciones p 
                    ON cp.id_presentacion = p.id_presentacion
                  WHERE cp.id_jornada = $id_jornada
                  ORDER BY cp.orden_salida ASC";

$resCronograma = $conn->query($sqlCronograma);

if (!$resCronograma) {
    echo json_encode([
        "ok" => false,
        "mensaje" => "Error al obtener cronograma: " . $conn->error
    ]);
    exit;
}

$cronograma = [];

while ($row = $resCronograma->fetch_assoc()) {
    $cronograma[] = $row;
}

$sqlConflictos = "SELECT 
                    cc.id_conflicto,
                    CONCAT(i.nombre, ' ', i.apellido) AS integrante,
                    p1.nombre_presentacion AS presentacion_1,
                    p2.nombre_presentacion AS presentacion_2,
                    cc.minutos_requeridos,
                    cc.minutos_disponibles,
                    cc.tipo_conflicto,
                    cc.observacion
                  FROM conflictos_cronograma cc
                  INNER JOIN integrantes i 
                    ON cc.id_integrante = i.id_integrante
                  INNER JOIN presentaciones p1 
                    ON cc.id_presentacion_1 = p1.id_presentacion
                  INNER JOIN presentaciones p2 
                    ON cc.id_presentacion_2 = p2.id_presentacion
                  WHERE cc.id_jornada = $id_jornada
                  ORDER BY cc.id_conflicto ASC";

$resConflictos = $conn->query($sqlConflictos);

if (!$resConflictos) {
    echo json_encode([
        "ok" => false,
        "mensaje" => "Error al obtener conflictos: " . $conn->error
    ]);
    exit;
}

$conflictos = [];

while ($row = $resConflictos->fetch_assoc()) {
    $conflictos[] = $row;
}

echo json_encode([
    "ok" => true,
    "cronograma" => $cronograma,
    "conflictos" => $conflictos
]);

$conn->close();
?>