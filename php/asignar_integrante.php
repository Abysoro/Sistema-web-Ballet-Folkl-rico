<?php

include "conexion.php";

$id_integrante = $_POST['id_integrante'];
$id_presentacion = $_POST['id_presentacion'];

/* verificar capacidad */

$sqlCapacidad = "SELECT capacidad FROM presentaciones WHERE id_presentacion='$id_presentacion'";

$resCap = $conn->query($sqlCapacidad);
$cap = $resCap->fetch_assoc()['capacidad'];

/* contar asignados */

$sqlConteo = "SELECT COUNT(*) as total FROM integrantes_presentacion WHERE id_presentacion='$id_presentacion'";

$resConteo = $conn->query($sqlConteo);
$total = $resConteo->fetch_assoc()['total'];

if($total >= $cap){

echo "La presentación ya alcanzó su capacidad";
exit;

}

/* asignar integrante */

$sql = "INSERT INTO integrantes_presentacion (id_integrante,id_presentacion)
VALUES ('$id_integrante','$id_presentacion')";

if($conn->query($sql)){

echo "OK";

}else{

echo "Error al asignar";

}

$conn->close();

?>