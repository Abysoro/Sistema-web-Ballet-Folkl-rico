<?php
if (!isset($conn)) {
    include __DIR__ . "/conexion.php";
}

/* Presentaciones pasadas pasan a Inactiva */
$sqlPresentaciones = "
UPDATE presentaciones
SET estado = 'Inactiva'
WHERE fecha < CURDATE()
AND estado = 'Activa'
";

mysqli_query($conn, $sqlPresentaciones);

/* Jornadas pasadas pasan a Inactivo, si tu tabla jornadas usa Activo/Inactivo */
$sqlJornadas = "
UPDATE jornadas
SET estado = 'Inactivo'
WHERE fecha < CURDATE()
AND estado = 'Activo'
";

mysqli_query($conn, $sqlJornadas);
?>