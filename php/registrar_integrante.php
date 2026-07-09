<?php
include "conexion.php";

$nombre = ucfirst(strtolower(trim($_POST['nombre'])));
$apellido = ucfirst(strtolower(trim($_POST['apellido'])));
$id_categoria = (int)$_POST['categoria'];
$id_nivel = (int)$_POST['nivel'];

$rapidez_aprendizaje = (int)$_POST['rapidez_aprendizaje'];
$anios_antiguedad = (int)$_POST['anios_antiguedad'];
$cantidad_presentaciones = (int)$_POST['cantidad_presentaciones'];
$puntaje_disciplina = (int)$_POST['puntaje_disciplina'];
$puntaje_responsabilidad = (int)$_POST['puntaje_responsabilidad'];

$fecha_registro = date("Y-m-d H:i:s");
$fecha_actualizacion = date("Y-m-d H:i:s");

/* Validaciones */
if ($nombre == "" || $apellido == "") {
    die("Nombre y apellido son obligatorios");
}

if ($rapidez_aprendizaje < 1 || $rapidez_aprendizaje > 5) {
    die("La rapidez de aprendizaje debe estar entre 1 y 5");
}

if ($puntaje_disciplina < 1 || $puntaje_disciplina > 5) {
    die("El puntaje de disciplina debe estar entre 1 y 5");
}

if ($puntaje_responsabilidad < 1 || $puntaje_responsabilidad > 5) {
    die("El puntaje de responsabilidad debe estar entre 1 y 5");
}

if ($anios_antiguedad < 0 || $cantidad_presentaciones < 0) {
    die("Los valores numéricos no pueden ser negativos");
}

mysqli_begin_transaction($conn);

try {

    /* Buscar si ya existía */
    $sql_check = "SELECT i.id_integrante, i.estado, i.id_categoria, i.id_nivel
                  FROM integrantes i
                  WHERE i.nombre = '$nombre' AND i.apellido = '$apellido'
                  LIMIT 1";

    $result = mysqli_query($conn, $sql_check);

    if (!$result) {
        throw new Exception("Error al verificar integrante: " . mysqli_error($conn));
    }

    if (mysqli_num_rows($result) > 0) {
        $existente = mysqli_fetch_assoc($result);
        $id_integrante = (int)$existente['id_integrante'];
        $estado = $existente['estado'];
        $categoria_anterior = (int)$existente['id_categoria'];
        $nivel_anterior = (int)$existente['id_nivel'];

        if ($estado === 'activo') {
            throw new Exception("Este integrante ya está registrado");
        }

        /* REACTIVAR integrante retirado */
        $sqlReactivar = "UPDATE integrantes
                         SET id_nivel = '$id_nivel',
                             id_categoria = '$id_categoria',
                             estado = 'activo',
                             fecha_reingreso = NOW(),
                             fecha_baja = NULL,
                             motivo_baja = NULL,
                             cantidad_reingresos = cantidad_reingresos + 1
                         WHERE id_integrante = $id_integrante";

        if (!mysqli_query($conn, $sqlReactivar)) {
            throw new Exception("Error al reactivar integrante: " . mysqli_error($conn));
        }

        /* actualizar o insertar criterios */
        $sqlExisteCriterio = "SELECT id_integrante FROM criterios_integrante WHERE id_integrante = $id_integrante LIMIT 1";
        $resCriterio = mysqli_query($conn, $sqlExisteCriterio);

        if ($resCriterio && mysqli_num_rows($resCriterio) > 0) {
            $sqlCriterios = "UPDATE criterios_integrante
                             SET rapidez_aprendizaje = $rapidez_aprendizaje,
                                 anios_antiguedad = $anios_antiguedad,
                                 cantidad_presentaciones = $cantidad_presentaciones,
                                 puntaje_disciplina = $puntaje_disciplina,
                                 puntaje_responsabilidad = $puntaje_responsabilidad,
                                 fecha_actualizacion = '$fecha_actualizacion'
                             WHERE id_integrante = $id_integrante";
        } else {
            $sqlCriterios = "INSERT INTO criterios_integrante
                             (id_integrante, rapidez_aprendizaje, anios_antiguedad, cantidad_presentaciones, puntaje_disciplina, puntaje_responsabilidad, fecha_actualizacion)
                             VALUES
                             ($id_integrante, $rapidez_aprendizaje, $anios_antiguedad, $cantidad_presentaciones, $puntaje_disciplina, $puntaje_responsabilidad, '$fecha_actualizacion')";
        }

        if (!mysqli_query($conn, $sqlCriterios)) {
            throw new Exception("Error al actualizar criterios: " . mysqli_error($conn));
        }

        /* guardar historial de reingreso */
        $sqlHistorial = "INSERT INTO historial_integrante
                         (id_integrante, tipo_movimiento, categoria_anterior, nivel_anterior, categoria_nueva, nivel_nuevo, observacion)
                         VALUES
                         ($id_integrante, 'reingreso', $categoria_anterior, $nivel_anterior, $id_categoria, $id_nivel, 'Reincorporación del integrante')";
        if (!mysqli_query($conn, $sqlHistorial)) {
            throw new Exception("Error al guardar historial de reingreso: " . mysqli_error($conn));
        }

        mysqli_commit($conn);
        echo "REACTIVADO";
        exit;
    }

    /* NUEVO integrante */
    $sqlIntegrante = "INSERT INTO integrantes
                      (nombre, apellido, id_nivel, id_categoria, fecha_registro, estado)
                      VALUES
                      ('$nombre', '$apellido', '$id_nivel', '$id_categoria', '$fecha_registro', 'activo')";

    if (!mysqli_query($conn, $sqlIntegrante)) {
        throw new Exception("Error al registrar integrante: " . mysqli_error($conn));
    }

    $id_integrante = mysqli_insert_id($conn);

    $sqlCriterios = "INSERT INTO criterios_integrante
                     (id_integrante, rapidez_aprendizaje, anios_antiguedad, cantidad_presentaciones, puntaje_disciplina, puntaje_responsabilidad, fecha_actualizacion)
                     VALUES
                     ($id_integrante, $rapidez_aprendizaje, $anios_antiguedad, $cantidad_presentaciones, $puntaje_disciplina, $puntaje_responsabilidad, '$fecha_actualizacion')";

    if (!mysqli_query($conn, $sqlCriterios)) {
        throw new Exception("Error al registrar criterios: " . mysqli_error($conn));
    }

    $sqlHistorialAlta = "INSERT INTO historial_integrante
                         (id_integrante, tipo_movimiento, categoria_nueva, nivel_nuevo, observacion)
                         VALUES
                         ($id_integrante, 'alta', $id_categoria, $id_nivel, 'Registro inicial del integrante')";
    if (!mysqli_query($conn, $sqlHistorialAlta)) {
        throw new Exception("Error al guardar historial de alta: " . mysqli_error($conn));
    }

    mysqli_commit($conn);
    echo "OK";

} catch (Exception $e) {
    mysqli_rollback($conn);
    echo $e->getMessage();
}

mysqli_close($conn);
?>