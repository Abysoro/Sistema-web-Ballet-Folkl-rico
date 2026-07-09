<?php
include "conexion.php";
include "normalizacion_presentacion.php";

$nombre = isset($_POST['nombre_presentacion']) ? trim($_POST['nombre_presentacion']) : '';
$id_categoria = isset($_POST['categoria']) ? (int)$_POST['categoria'] : 0;
$capacidad = isset($_POST['capacidad']) ? (int)$_POST['capacidad'] : 0;

$fecha = isset($_POST['fecha']) ? $_POST['fecha'] : '';
$id_tipo_evento = isset($_POST['tipo_evento']) ? (int)$_POST['tipo_evento'] : 0;
$otro_tipo_evento = isset($_POST['otro_tipo_evento']) ? trim($_POST['otro_tipo_evento']) : '';
$lugar = isset($_POST['lugar']) ? trim($_POST['lugar']) : '';
$nivel_minimo = isset($_POST['nivel_minimo']) ? (int)$_POST['nivel_minimo'] : 0;
$duracion_minutos = isset($_POST['duracion_minutos']) ? (int)$_POST['duracion_minutos'] : 8;

/* jornada puede ir vacía */
$id_jornada = (isset($_POST['jornada']) && $_POST['jornada'] !== '') ? (int)$_POST['jornada'] : null;

/* como no están en el formulario, se guardan como 0 */
$rapidez_minima = 0;
$antiguedad_minima = 0;

if (
    $nombre === "" ||
    $id_tipo_evento <= 0 ||
    $id_categoria <= 0 ||
    $capacidad <= 0 ||
    $fecha === "" ||
    $lugar === "" ||
    $nivel_minimo <= 0 ||
    $duracion_minutos <= 0
) {
    die("Completa correctamente los datos de la presentación");
}

date_default_timezone_set('America/La_Paz');

$hoy = date('Y-m-d');

if ($fecha < $hoy) {
    die("No se permiten fechas pasadas para la presentación.");
}

mysqli_begin_transaction($conn);

try {

    /*
        Normalización:
        - Busca la danza en la tabla danzas.
        - Si no existe, la registra.
        - Busca el lugar en la tabla lugares.
        - Si no existe, lo registra.
    */
    $id_danza = obtenerOCrearDanza($conn, $nombre);
    $id_lugar_normalizado = obtenerOCrearLugar($conn, $lugar);

    if ($id_danza === null || $id_lugar_normalizado === null) {
        throw new Exception("No se pudo normalizar la danza o el lugar.");
    }

    /* escapar texto después de normalizar */
    $nombre = $conn->real_escape_string($nombre);
    $lugar = $conn->real_escape_string($lugar);
    $otro_tipo_evento = $conn->real_escape_string($otro_tipo_evento);

    $valorJornada = ($id_jornada === null) ? "NULL" : $id_jornada;
    $valorHoraPresentacion = "NULL";
    $valorOtroTipo = ($otro_tipo_evento !== '') ? "'$otro_tipo_evento'" : "NULL";

    /* si eligió jornada, copiar hora_inicio a hora_presentacion */
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

    $sqlPresentacion = "INSERT INTO presentaciones
                        (
                            id_danza,
                            nombre_presentacion,
                            id_tipo_evento,
                            otro_tipo_evento,
                            id_categoria,
                            capacidad,
                            fecha,
                            hora_presentacion,
                            lugar,
                            id_lugar,
                            id_jornada,
                            duracion_minutos,
                            estado
                        )
                        VALUES
                        (
                            $id_danza,
                            '$nombre',
                            $id_tipo_evento,
                            $valorOtroTipo,
                            '$id_categoria',
                            '$capacidad',
                            '$fecha',
                            $valorHoraPresentacion,
                            '$lugar',
                            $id_lugar_normalizado,
                            $valorJornada,
                            '$duracion_minutos',
                            'Activa'
                        )";

    if (!$conn->query($sqlPresentacion)) {
        throw new Exception("Error al registrar presentación: " . $conn->error);
    }

    $id_presentacion = $conn->insert_id;

    $sqlRequisito = "INSERT INTO requisitos_presentacion
                     (id_presentacion, nivel_minimo, rapidez_minima, antiguedad_minima, observaciones)
                     VALUES
                     ($id_presentacion, $nivel_minimo, $rapidez_minima, $antiguedad_minima, 'Registrado desde el sistema')";

    if (!$conn->query($sqlRequisito)) {
        throw new Exception("Error al registrar requisitos: " . $conn->error);
    }

    mysqli_commit($conn);
    echo "OK";

} catch (Exception $e) {
    mysqli_rollback($conn);
    echo $e->getMessage();
}

$conn->close();
?>