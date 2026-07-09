<?php

include "conexion.php";

header('Content-Type: application/json; charset=utf-8');

$sql = "SELECT 
            i.nombre,
            i.apellido,
            p.nombre_presentacion,
            c.nombre_categoria
        FROM integrantes_presentacion ip
        INNER JOIN integrantes i ON ip.id_integrante = i.id_integrante
        INNER JOIN presentaciones p ON ip.id_presentacion = p.id_presentacion
        INNER JOIN categorias c ON p.id_categoria = c.id_categoria
        WHERE p.estado = 'Activa'
        AND p.fecha >= CURDATE()
        ORDER BY p.fecha ASC, p.hora_presentacion ASC, i.nombre ASC";

$resultado = $conn->query($sql);

$datos = [];

if ($resultado) {
    while ($fila = $resultado->fetch_assoc()) {
        $datos[] = $fila;
    }
}

echo json_encode($datos);

$conn->close();

?>