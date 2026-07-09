<?php
include "conexion.php";
include "funciones_cronograma.php";

header('Content-Type: application/json; charset=utf-8');

$id_jornada = isset($_GET['id_jornada']) ? (int)$_GET['id_jornada'] : 0;

if ($id_jornada <= 0) {
    echo json_encode([]);
    exit;
}

try {
    // Ahora las presentaciones no incluyen 'hora_presentacion'
    $presentaciones = obtenerPresentacionesDeJornada($conn, $id_jornada);
    
    echo json_encode(array_values($presentaciones));
} catch (Exception $e) {
    echo json_encode([
        "ok" => false,
        "mensaje" => $e->getMessage()
    ]);
}

$conn->close();
?>