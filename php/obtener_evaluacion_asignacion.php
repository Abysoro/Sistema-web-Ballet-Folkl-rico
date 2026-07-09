<?php
include "conexion.php";

header("Content-Type: application/json; charset=utf-8");

date_default_timezone_set("America/La_Paz");
mysqli_query($conn, "SET time_zone = '-04:00'");

$sql = "
SELECT
    ea.id_evaluacion,
    ea.id_presentacion AS id_presentacion,
    p.nombre_presentacion,
    CONCAT(i.nombre, ' ', i.apellido) AS nombre_integrante,
    c.nombre_categoria,
    n.nombre_nivel,
    ea.puntaje_nivel,
    ea.puntaje_rapidez,
    ea.puntaje_antiguedad,
    ea.puntaje_equidad,
    ea.puntaje_experiencia,
    ea.puntaje_total,
    ea.decision_final,
    ea.motivo,
    ea.fecha_evaluacion
FROM evaluacion_asignacion ea
INNER JOIN presentaciones p 
    ON ea.id_presentacion = p.id_presentacion
INNER JOIN integrantes i 
    ON ea.id_integrante = i.id_integrante
INNER JOIN categorias c 
    ON p.id_categoria = c.id_categoria
INNER JOIN niveles n 
    ON i.id_nivel = n.id_nivel
INNER JOIN jornadas j
    ON p.id_jornada = j.id_jornada

WHERE p.estado = 'Activa'
AND p.fecha >= CURDATE()
AND j.estado = 'Activo'

ORDER BY 
    p.fecha ASC,
    p.hora_presentacion ASC,
    p.nombre_presentacion ASC,
    c.nombre_categoria ASC,
    i.apellido ASC,
    i.nombre ASC
";

$resultado = $conn->query($sql);

$datos = [];

if ($resultado) {
    while ($fila = $resultado->fetch_assoc()) {
        $datos[] = $fila;
    }
} else {
    echo json_encode([
        "ok" => false,
        "mensaje" => "Error al obtener evaluación: " . $conn->error
    ], JSON_UNESCAPED_UNICODE);
    $conn->close();
    exit;
}

echo json_encode($datos, JSON_UNESCAPED_UNICODE);

$conn->close();
?>