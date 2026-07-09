<?php
include("conexion.php");

$id = null;

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
} elseif (isset($_POST['id'])) {
    $id = intval($_POST['id']);
}

if (!$id) {
    die("ID no recibido");
}

mysqli_begin_transaction($conn);

try {
    // 1. Borrar evaluaciones relacionadas
    $sql0 = "DELETE FROM evaluacion_asignacion WHERE id_presentacion = $id";
    if (!mysqli_query($conn, $sql0)) {
        throw new Exception(mysqli_error($conn));
    }

    // 2. Borrar conflictos relacionados
    $sqlConflictos = "DELETE FROM conflictos_cronograma 
                      WHERE id_presentacion_1 = $id OR id_presentacion_2 = $id";
    if (!mysqli_query($conn, $sqlConflictos)) {
        throw new Exception(mysqli_error($conn));
    }

    // 3. Borrar registros relacionados en cronograma_presentacion
    $sqlCronograma = "DELETE FROM cronograma_presentacion WHERE id_presentacion = $id";
    if (!mysqli_query($conn, $sqlCronograma)) {
        throw new Exception(mysqli_error($conn));
    }

    // 4. Borrar registros relacionados en detalle_asignacion
    $sql1 = "DELETE FROM detalle_asignacion WHERE id_presentacion = $id";
    if (!mysqli_query($conn, $sql1)) {
        throw new Exception(mysqli_error($conn));
    }

    $sqlSuplentes = "DELETE FROM suplentes_presentacion WHERE id_presentacion = $id";
    if (!mysqli_query($conn, $sqlSuplentes)) {
        throw new Exception(mysqli_error($conn));
    }

    // 6. Borrar registros relacionados en integrantes_presentacion
    $sql2 = "DELETE FROM integrantes_presentacion WHERE id_presentacion = $id";
    if (!mysqli_query($conn, $sql2)) {
        throw new Exception(mysqli_error($conn));
    }

    // 7. Borrar registros relacionados en cola_asignacion
    $sql3 = "DELETE FROM cola_asignacion WHERE id_presentacion = $id";
    if (!mysqli_query($conn, $sql3)) {
        throw new Exception(mysqli_error($conn));
    }

    // 8. Borrar requisitos relacionados
    $sql4 = "DELETE FROM requisitos_presentacion WHERE id_presentacion = $id";
    if (!mysqli_query($conn, $sql4)) {
        throw new Exception(mysqli_error($conn));
    }

    // 9. Borrar métricas relacionadas si existen
    $sql5 = "DELETE FROM metricas_cola WHERE id_presentacion = $id";
    if (!mysqli_query($conn, $sql5)) {
        throw new Exception(mysqli_error($conn));
    }

    // 10. Finalmente borrar la presentación
    $sql6 = "DELETE FROM presentaciones WHERE id_presentacion = $id";
    if (!mysqli_query($conn, $sql6)) {
        throw new Exception(mysqli_error($conn));
    }

    mysqli_commit($conn);
    echo "OK";

} catch (Exception $e) {
    mysqli_rollback($conn);
    echo "error: " . $e->getMessage();
}

mysqli_close($conn);
?>