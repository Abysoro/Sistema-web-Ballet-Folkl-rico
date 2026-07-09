<?php
include "conexion.php";
include "funciones_cronograma.php";

header('Content-Type: text/plain; charset=utf-8');

$id_jornada = isset($_GET['id_jornada']) ? (int)$_GET['id_jornada'] : 0;

if ($id_jornada <= 0) {
    exit("Jornada no válida");
}

mysqli_begin_transaction($conn);

try {
    $resultado = optimizarCronogramaCompleto($conn, $id_jornada);
    $cronograma = $resultado['cronograma'];
    $conflictos = $resultado['conflictos'];

    $sqlDeleteCronograma = "DELETE FROM cronograma_presentacion WHERE id_jornada = $id_jornada";
    if (!$conn->query($sqlDeleteCronograma)) {
        throw new Exception("Error al limpiar cronograma anterior: " . $conn->error);
    }

    $sqlDeleteConflictos = "DELETE FROM conflictos_cronograma WHERE id_jornada = $id_jornada";
    if (!$conn->query($sqlDeleteConflictos)) {
        throw new Exception("Error al limpiar conflictos anteriores: " . $conn->error);
    }

    foreach ($cronograma as $item) {
        $id_presentacion = (int)$item['id_presentacion'];
        $orden_salida = (int)$item['orden_salida'];
        $hora_inicio = $conn->real_escape_string($item['hora_inicio_estimada']);
        $hora_fin = $conn->real_escape_string($item['hora_fin_estimada']);
        $conflictos_previos = (int)$item['conflictos_previos'];
        $observacion = $conn->real_escape_string($item['observacion']);
        $estado = "Generado";

        $sqlInsert = "INSERT INTO cronograma_presentacion
                      (id_jornada, id_presentacion, orden_salida, hora_inicio_estimada, hora_fin_estimada, conflictos_previos, observacion, estado)
                      VALUES
                      ($id_jornada, $id_presentacion, $orden_salida, '$hora_inicio', '$hora_fin', $conflictos_previos, '$observacion', '$estado')";

        if (!$conn->query($sqlInsert)) {
            throw new Exception("Error al guardar cronograma: " . $conn->error);
        }
    }

    foreach ($conflictos as $c) {
        $id_integrante = (int)$c['id_integrante'];
        $id_presentacion_1 = (int)$c['id_presentacion_1'];
        $id_presentacion_2 = (int)$c['id_presentacion_2'];
        $minutos_requeridos = (int)$c['minutos_requeridos'];
        $minutos_disponibles = (int)$c['minutos_disponibles'];
        $observacion = $conn->real_escape_string(
            "El integrante requiere $minutos_requeridos min y solo dispone de $minutos_disponibles min."
        );

        $sqlInsertConflicto = "INSERT INTO conflictos_cronograma
                               (id_jornada, id_integrante, id_presentacion_1, id_presentacion_2, minutos_requeridos, minutos_disponibles, tipo_conflicto, observacion)
                               VALUES
                               ($id_jornada, $id_integrante, $id_presentacion_1, $id_presentacion_2, $minutos_requeridos, $minutos_disponibles, 'Cambio de vestuario', '$observacion')";

        if (!$conn->query($sqlInsertConflicto)) {
            throw new Exception("Error al guardar conflicto: " . $conn->error);
        }
    }

    mysqli_commit($conn);

    echo "OK";
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo "Error: " . $e->getMessage();
}

$conn->close();
?>