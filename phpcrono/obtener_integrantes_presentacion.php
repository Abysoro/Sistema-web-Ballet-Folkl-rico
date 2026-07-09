<?php
include "conexion.php";
header('Content-Type: application/json; charset=utf-8');

$id_presentacion = isset($_GET['id_presentacion']) ? (int)$_GET['id_presentacion'] : 0;

if ($id_presentacion <= 0) {
    echo json_encode([
        "ok" => false,
        "mensaje" => "ID de presentación inválido"
    ]);
    exit;
}

$sql = "
    SELECT i.nombre, i.apellido
    FROM integrantes_presentacion ip
    INNER JOIN integrantes i ON ip.id_integrante = i.id_integrante
    WHERE ip.id_presentacion = $id_presentacion
";

$res = $conn->query($sql);

$integrantes = [];

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $integrantes[] = $row;
    }
}

echo json_encode([
    "ok" => true,
    "integrantes" => $integrantes
]);

$conn->close();
?>