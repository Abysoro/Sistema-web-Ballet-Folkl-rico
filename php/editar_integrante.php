<?php

include "conexion.php";

$id = $_POST['id'];
$nombre = $_POST['nombre'];
$apellido = $_POST['apellido'];
$categoria = $_POST['categoria'];
$nivel = $_POST['nivel'];

$sql = "UPDATE integrantes 
SET nombre='$nombre',
apellido='$apellido',
id_categoria='$categoria',
id_nivel='$nivel'
WHERE id_integrante='$id'";

if($conn->query($sql)){
echo "OK";
}else{
echo "Error";
}

$conn->close();

?>