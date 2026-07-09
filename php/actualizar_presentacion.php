<?php
include "conexion.php";
include "normalizacion_presentacion.php";

$id = isset($_POST['id_presentacion']) ? (int)$_POST['id_presentacion'] : 0;
$nombre = isset($_POST['nombre_presentacion']) ? trim($_POST['nombre_presentacion']) : '';
$id_tipo_evento = isset($_POST['tipo_evento']) ? (int)$_POST['tipo_evento'] : 0;
$otro_tipo_evento = isset($_POST['otro_tipo_evento']) ? trim($_POST['otro_tipo_evento']) : '';
$categoria = isset($_POST['categoria']) ? (int)$_POST['categoria'] : 0;
$capacidad = isset($_POST['capacidad']) ? (int)$_POST['capacidad'] : 0;
$nivel_minimo = isset($_POST['nivel_minimo']) ? (int)$_POST['nivel_minimo'] : 0;
$fecha = isset($_POST['fecha']) ? $_POST['fecha'] : '';
$lugar = isset($_POST['lugar']) ? trim($_POST['lugar']) : '';
$duracion_minutos = isset($_POST['duracion_minutos']) ? (int)$_POST['duracion_minutos'] : 8;

$id_jornada = (isset($_POST['jornada']) && $_POST['jornada'] !== '') ? (int)$_POST['jornada'] : null;

if (
    $id <= 0 ||
    $nombre === '' ||
    $id_tipo_evento <= 0 ||
    $categoria <= 0 ||
    $capacidad <= 0 ||
    $nivel_minimo <= 0 ||
    $fecha === '' ||
    $lugar === '' ||
    $duracion_minutos <= 0
) {
    die("Completa correctamente los datos de la presentación.");
}

date_default_timezone_set('America/La_Paz');

$hoy = date('Y-m-d');

if ($fecha < $hoy) {
    die("No se permiten fechas pasadas para la presentación.");
}

mysqli_begin_transaction($conn);

try {
    // Obtener o crear la danza y el lugar normalizados
    $id_danza = obtenerOCrearDanza($conn, $nombre);
    $id_lugar_normalizado = obtenerOCrearLugar($conn, $lugar);

    if ($id_danza === null || $id_lugar_normalizado === null) {
        throw new Exception("No se pudo normalizar la danza o el lugar.");
    }

    // Escapar textos para mantener compatibilidad con las columnas antiguas
    $nombre = $conn->real_escape_string($nombre);
    $lugar = $conn->real_escape_string($lugar);
    $otro_tipo_evento = $conn->real_escape_string($otro_tipo_evento);
    $valorJornada = ($id_jornada === null) ? "NULL" : $id_jornada;
    $valorHoraPresentacion = "NULL";
    $valorOtroTipo = ($otro_tipo_evento !== '') ? "'$otro_tipo_evento'" : "NULL";

    if ($id_jornada !== null) {
        $sqlHora = "SELECT hora_inicio
                    FROM jornadas
                    WHERE id_jornada = $id_jornada
                    LIMIT 1";
        $resHora = $conn->query($sqlHora);

        if (!$resHora || $resHora->num_rows === 0) {
            throw new Exception("La jornada seleccionada no existe.");
        }

        $filaHora = $resHora->fetch_assoc();

        if (!empty($filaHora['hora_inicio'])) {
            $hora_inicio = $conn->real_escape_string($filaHora['hora_inicio']);
            $valorHoraPresentacion = "'$hora_inicio'";
        }
    }

    $sql1 = "UPDATE presentaciones
         SET nombre_presentacion = '$nombre',
             id_danza = $id_danza,
             id_tipo_evento = $id_tipo_evento,
             otro_tipo_evento = $valorOtroTipo,
             id_categoria = '$categoria',
             capacidad = '$capacidad',
             fecha = '$fecha',
             lugar = '$lugar',
             id_lugar = $id_lugar_normalizado,
             id_jornada = $valorJornada,
             hora_presentacion = $valorHoraPresentacion,
             duracion_minutos = '$duracion_minutos'
         WHERE id_presentacion = '$id'";

    if (!$conn->query($sql1)) {
        throw new Exception("Error al actualizar presentación: " . $conn->error);
    }

    $sqlVerificar = "SELECT id_presentacion
                     FROM requisitos_presentacion
                     WHERE id_presentacion = '$id'";
    $resVerificar = $conn->query($sqlVerificar);

    if ($resVerificar && $resVerificar->num_rows > 0) {
        $sql2 = "UPDATE requisitos_presentacion
                 SET nivel_minimo = '$nivel_minimo'
                 WHERE id_presentacion = '$id'";
    } else {
        $sql2 = "INSERT INTO requisitos_presentacion
                 (id_presentacion, nivel_minimo)
                 VALUES
                 ('$id', '$nivel_minimo')";
    }

    if (!$conn->query($sql2)) {
        throw new Exception("Error al actualizar requisitos: " . $conn->error);
    }

    mysqli_commit($conn);
    echo "OK";

} catch (Exception $e) {
    mysqli_rollback($conn);
    echo $e->getMessage();
}

$conn->close();
?>