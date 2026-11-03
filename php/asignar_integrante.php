<?php
include "conexion.php";

date_default_timezone_set('America/La_Paz');
header("Content-Type: text/plain; charset=utf-8");

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$id_integrante = isset($_POST['id_integrante'])
    ? (int)$_POST['id_integrante'] : 0;

$id_presentacion = isset($_POST['id_presentacion'])
    ? (int)$_POST['id_presentacion'] : 0;

if ($id_integrante <= 0 || $id_presentacion <= 0) {
    exit("Datos de asignación no válidos.");
}

try {
    $conn->query("SET time_zone = '-04:00'");
    $conn->begin_transaction();

    // 1. Verificar presentación y capacidad.
    $stmt = $conn->prepare("
        SELECT capacidad, id_categoria
        FROM presentaciones
        WHERE id_presentacion = ?
        FOR UPDATE
    ");
    $stmt->bind_param("i", $id_presentacion);
    $stmt->execute();
    $presentacion = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$presentacion) {
        throw new Exception("La presentación no existe.");
    }

    $capacidad = (int)$presentacion['capacidad'];
    $id_categoria = (int)$presentacion['id_categoria'];

    // 2. Evitar asignaciones duplicadas.
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM integrantes_presentacion
        WHERE id_integrante = ?
          AND id_presentacion = ?
    ");
    $stmt->bind_param("ii", $id_integrante, $id_presentacion);
    $stmt->execute();
    $existe = (int)$stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    if ($existe > 0) {
        throw new Exception("El integrante ya está asignado a esta presentación.");
    }

    // 3. Comprobar cupos disponibles.
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM integrantes_presentacion
        WHERE id_presentacion = ?
    ");
    $stmt->bind_param("i", $id_presentacion);
    $stmt->execute();
    $total = (int)$stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    if ($total >= $capacidad) {
        throw new Exception("La presentación ya alcanzó su capacidad.");
    }

    // 4. Consultar la fecha original de ingreso a cola.
    $stmt = $conn->prepare("
        SELECT id_cola, fecha_ingreso, estado
        FROM cola_asignacion
        WHERE id_integrante = ?
          AND id_presentacion = ?
        LIMIT 1
        FOR UPDATE
    ");
    $stmt->bind_param("ii", $id_integrante, $id_presentacion);
    $stmt->execute();
    $cola = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($cola) {

        if ($cola['estado'] !== 'esperando') {
            throw new Exception(
                "El registro de cola no está en espera. Verifique su estado."
            );
        }

        $id_cola = (int)$cola['id_cola'];
        $fecha_ingreso = $cola['fecha_ingreso'];

        if (empty($fecha_ingreso)) {
            throw new Exception(
                "El registro de cola no tiene fecha de ingreso válida."
            );
        }

    } else {

        // Si nunca ingresó a cola, registrar ingreso actual.
        $stmt = $conn->prepare("
            INSERT INTO cola_asignacion
            (
                id_integrante,
                id_presentacion,
                fecha_ingreso,
                estado,
                prioridad,
                prioridad_calculada,
                veces_no_asignado
            )
            VALUES (?, ?, NOW(), 'esperando', 0, 0, 0)
        ");
        $stmt->bind_param("ii", $id_integrante, $id_presentacion);
        $stmt->execute();
        $id_cola = $conn->insert_id;
        $stmt->close();

        $stmt = $conn->prepare("
            SELECT fecha_ingreso
            FROM cola_asignacion
            WHERE id_cola = ?
        ");
        $stmt->bind_param("i", $id_cola);
        $stmt->execute();
        $fecha_ingreso = $stmt->get_result()
            ->fetch_assoc()['fecha_ingreso'];
        $stmt->close();
    }

    // 5. Registrar la asignación.
    $stmt = $conn->prepare("
        INSERT INTO integrantes_presentacion
        (id_integrante, id_presentacion)
        VALUES (?, ?)
    ");
    $stmt->bind_param("ii", $id_integrante, $id_presentacion);
    $stmt->execute();
    $stmt->close();

    // 6. Registrar el momento real de asignación.
    $fecha_asignacion = $conn->query("
        SELECT NOW() AS momento
    ")->fetch_assoc()['momento'];

    $segundos_espera = max(
        0,
        strtotime($fecha_asignacion) - strtotime($fecha_ingreso)
    );

    // El campo tiempo_espera existente se conserva en minutos.
    $minutos_espera = intdiv($segundos_espera, 60);

    // 7. Actualizar estado de cola.
    $stmt = $conn->prepare("
        UPDATE cola_asignacion
        SET estado = 'asignado'
        WHERE id_cola = ?
    ");
    $stmt->bind_param("i", $id_cola);
    $stmt->execute();
    $stmt->close();

    // 8. Verificar si ya existe detalle de asignación.
    $stmt = $conn->prepare("
        SELECT id_detalle, estado_asignacion
        FROM detalle_asignacion
        WHERE id_integrante = ?
          AND id_presentacion = ?
        ORDER BY id_detalle DESC
        LIMIT 1
    ");
    $stmt->bind_param("ii", $id_integrante, $id_presentacion);
    $stmt->execute();
    $detalle = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($detalle) {

        if ($detalle['estado_asignacion'] === 'Asignado') {
            throw new Exception(
                "El detalle ya registra una asignación. Revise la consistencia."
            );
        }

        $id_detalle = (int)$detalle['id_detalle'];

        $stmt = $conn->prepare("
            UPDATE detalle_asignacion
            SET fecha_ingreso_cola = ?,
                fecha_asignacion = ?,
                tiempo_espera = ?,
                estado_asignacion = 'Asignado',
                criterio_seleccion = 'Asignación manual'
            WHERE id_detalle = ?
        ");

        $stmt->bind_param(
            "ssii",
            $fecha_ingreso,
            $fecha_asignacion,
            $minutos_espera,
            $id_detalle
        );

    } else {

        $stmt = $conn->prepare("
            INSERT INTO detalle_asignacion
            (
                id_integrante,
                id_presentacion,
                id_categoria,
                fecha_ingreso_cola,
                fecha_asignacion,
                tiempo_espera,
                estado_asignacion,
                veces_no_asignado,
                criterio_seleccion
            )
            VALUES (?, ?, ?, ?, ?, ?, 'Asignado', 0, 'Asignación manual')
        ");

        $stmt->bind_param(
            "iiissi",
            $id_integrante,
            $id_presentacion,
            $id_categoria,
            $fecha_ingreso,
            $fecha_asignacion,
            $minutos_espera
        );
    }

    $stmt->execute();
    $stmt->close();

    // Confirmar todas las operaciones juntas.
    $conn->commit();

    echo "OK";

} catch (Throwable $e) {

    $conn->rollback();
    http_response_code(400);

    echo "Error: " . $e->getMessage();
}

$conn->close();
?>