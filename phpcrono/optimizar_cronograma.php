<?php
include "conexion.php";
include "funciones_cronograma.php";

header('Content-Type: application/json; charset=utf-8');

$id_jornada = isset($_GET['id_jornada']) ? (int)$_GET['id_jornada'] : 0;

if ($id_jornada <= 0) {
    echo json_encode([
        "ok" => false,
        "mensaje" => "Jornada no válida."
    ]);
    exit;
}

try {
    $resultado = optimizarCronogramaCompleto($conn, $id_jornada);

    echo json_encode([
        "ok" => true,
        "jornada" => $resultado['jornada'],
        "presentaciones" => $resultado['presentaciones'],
        "cronograma" => $resultado['cronograma'],
        "conflictos" => $resultado['conflictos'],
        "resumen" => $resultado['resumen']
    ]);
} catch (Exception $e) {
    echo json_encode([
        "ok" => false,
        "mensaje" => $e->getMessage()
    ]);
}

$conn->close();
?>