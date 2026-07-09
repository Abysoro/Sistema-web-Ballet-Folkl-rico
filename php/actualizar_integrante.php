<?php
include "conexion.php";

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
$apellido = isset($_POST['apellido']) ? trim($_POST['apellido']) : '';
$categoria = isset($_POST['categoria']) ? (int)$_POST['categoria'] : 0;
$nivel = isset($_POST['nivel']) ? (int)$_POST['nivel'] : 0;

$rapidez_aprendizaje = isset($_POST['rapidez_aprendizaje']) ? (int)$_POST['rapidez_aprendizaje'] : 0;
$anios_antiguedad = isset($_POST['anios_antiguedad']) ? (int)$_POST['anios_antiguedad'] : 0;
$cantidad_presentaciones = isset($_POST['cantidad_presentaciones']) ? (int)$_POST['cantidad_presentaciones'] : 0;
$puntaje_disciplina = isset($_POST['puntaje_disciplina']) ? (int)$_POST['puntaje_disciplina'] : 0;
$puntaje_responsabilidad = isset($_POST['puntaje_responsabilidad']) ? (int)$_POST['puntaje_responsabilidad'] : 0;

if ($id <= 0 || $nombre === '' || $apellido === '' || $categoria <= 0 || $nivel <= 0) {
    die("Completa correctamente los datos del integrante.");
}

mysqli_begin_transaction($conn);

try {

    $sql1 = "UPDATE integrantes 
             SET nombre = '$nombre',
                 apellido = '$apellido',
                 id_categoria = '$categoria',
                 id_nivel = '$nivel'
             WHERE id_integrante = '$id'";

    if (!$conn->query($sql1)) {
        throw new Exception("Error al actualizar integrante: " . $conn->error);
    }

    $sqlVerificar = "SELECT id_criterio 
                     FROM criterios_integrante 
                     WHERE id_integrante = '$id'";
    $resVerificar = $conn->query($sqlVerificar);

    if ($resVerificar && $resVerificar->num_rows > 0) {
        $sql2 = "UPDATE criterios_integrante
                 SET rapidez_aprendizaje = '$rapidez_aprendizaje',
                     anios_antiguedad = '$anios_antiguedad',
                     cantidad_presentaciones = '$cantidad_presentaciones',
                     puntaje_disciplina = '$puntaje_disciplina',
                     puntaje_responsabilidad = '$puntaje_responsabilidad',
                     fecha_actualizacion = NOW()
                 WHERE id_integrante = '$id'";
    } else {
        $sql2 = "INSERT INTO criterios_integrante
                 (
                    id_integrante,
                    rapidez_aprendizaje,
                    anios_antiguedad,
                    cantidad_presentaciones,
                    puntaje_disciplina,
                    puntaje_responsabilidad,
                    fecha_actualizacion
                 )
                 VALUES
                 (
                    '$id',
                    '$rapidez_aprendizaje',
                    '$anios_antiguedad',
                    '$cantidad_presentaciones',
                    '$puntaje_disciplina',
                    '$puntaje_responsabilidad',
                    NOW()
                 )";
    }

    if (!$conn->query($sql2)) {
        throw new Exception("Error al actualizar criterios: " . $conn->error);
    }

    mysqli_commit($conn);
    echo "OK";

} catch (Exception $e) {
    mysqli_rollback($conn);
    echo $e->getMessage();
}

$conn->close();
?>