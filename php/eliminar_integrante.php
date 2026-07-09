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
    $sqlBuscar = "SELECT id_integrante, id_categoria, id_nivel, estado
                  FROM integrantes
                  WHERE id_integrante = $id
                  LIMIT 1";
    $resBuscar = mysqli_query($conn, $sqlBuscar);

    if (!$resBuscar || mysqli_num_rows($resBuscar) === 0) {
        throw new Exception("El integrante no existe.");
    }

    $integrante = mysqli_fetch_assoc($resBuscar);

    if ($integrante['estado'] === 'retirado') {
        mysqli_commit($conn);
        echo "OK";
        exit;
    }

    $id_categoria = (int)$integrante['id_categoria'];
    $id_nivel = (int)$integrante['id_nivel'];

    /* retiro lógico: NO borrar historial */
    $sqlUpdate = "UPDATE integrantes
                  SET estado = 'retirado',
                      fecha_baja = NOW(),
                      motivo_baja = 'Retiro desde el sistema'
                  WHERE id_integrante = $id";
    if (!mysqli_query($conn, $sqlUpdate)) {
        throw new Exception(mysqli_error($conn));
    }

    /* guardar historial */
    $sqlHistorial = "INSERT INTO historial_integrante
                     (id_integrante, tipo_movimiento, categoria_anterior, nivel_anterior, observacion)
                     VALUES
                     ($id, 'retiro', $id_categoria, $id_nivel, 'Retiro del elenco desde el sistema')";
    if (!mysqli_query($conn, $sqlHistorial)) {
        throw new Exception(mysqli_error($conn));
    }

    /* opcional: limpiar solo cola actual, no historial */
    mysqli_query($conn, "DELETE FROM cola_asignacion WHERE id_integrante = $id");
    mysqli_query($conn, "DELETE FROM cola WHERE id_integrante = $id");

    mysqli_commit($conn);
    echo "OK";

} catch (Exception $e) {
    mysqli_rollback($conn);
    echo "error: " . $e->getMessage();
}

mysqli_close($conn);
?>