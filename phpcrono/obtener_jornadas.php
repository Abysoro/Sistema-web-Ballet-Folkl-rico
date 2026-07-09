<?php
include "conexion.php";

header('Content-Type: application/json; charset=utf-8');

/*
    Solo muestra jornadas actuales o futuras.
    - Si la fecha es mayor a hoy, aparece.
    - Si la fecha es hoy, aparece solo si todavía no terminó según hora_fin.
    - Si hora_fin está vacía, igual se permite mostrar la jornada de hoy.
*/

$sql = "SELECT 
            id_jornada, 
            nombre_jornada, 
            fecha, 
            lugar, 
            hora_inicio, 
            hora_fin, 
            estado
        FROM jornadas
        WHERE estado = 'Activo'
        AND (
            fecha > CURDATE()
            OR (
                fecha = CURDATE()
                AND (
                    hora_fin IS NULL 
                    OR hora_fin = '00:00:00'
                    OR hora_fin >= CURTIME()
                )
            )
        )
        ORDER BY fecha ASC, hora_inicio ASC, nombre_jornada ASC";

$res = $conn->query($sql);

if (!$res) {
    echo json_encode([
        "ok" => false,
        "mensaje" => "Error al obtener jornadas: " . $conn->error
    ]);
    exit;
}

$datos = [];

while ($row = $res->fetch_assoc()) {
    $datos[] = $row;
}

echo json_encode([
    "ok" => true,
    "jornadas" => $datos
]);

$conn->close();
?>