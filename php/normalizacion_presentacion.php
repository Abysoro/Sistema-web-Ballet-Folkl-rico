<?php

function obtenerOCrearDanza($conn, $nombre_danza) {
    $conn->set_charset("utf8mb4");

    $nombre_danza = trim($nombre_danza);

    if ($nombre_danza === '') {
        return null;
    }

    $sqlBuscar = "
        SELECT id_danza 
        FROM danzas 
        WHERE LOWER(TRIM(nombre_danza)) COLLATE utf8mb4_general_ci
            = LOWER(TRIM(CONVERT(? USING utf8mb4))) COLLATE utf8mb4_general_ci
        LIMIT 1
    ";

    $stmt = $conn->prepare($sqlBuscar);

    if (!$stmt) {
        throw new Exception("Error al preparar búsqueda de danza: " . $conn->error);
    }

    $stmt->bind_param("s", $nombre_danza);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($fila = $resultado->fetch_assoc()) {
        return (int)$fila['id_danza'];
    }

    $sqlInsertar = "
        INSERT INTO danzas (nombre_danza, estado)
        VALUES (?, 'Activo')
    ";

    $stmt = $conn->prepare($sqlInsertar);

    if (!$stmt) {
        throw new Exception("Error al preparar inserción de danza: " . $conn->error);
    }

    $stmt->bind_param("s", $nombre_danza);
    $stmt->execute();

    return (int)$conn->insert_id;
}

function obtenerOCrearLugar($conn, $nombre_lugar) {
    $conn->set_charset("utf8mb4");

    $nombre_lugar = trim($nombre_lugar);

    if ($nombre_lugar === '') {
        return null;
    }

    $sqlBuscar = "
        SELECT id_lugar 
        FROM lugares 
        WHERE LOWER(TRIM(nombre_lugar)) COLLATE utf8mb4_general_ci
            = LOWER(TRIM(CONVERT(? USING utf8mb4))) COLLATE utf8mb4_general_ci
        LIMIT 1
    ";

    $stmt = $conn->prepare($sqlBuscar);

    if (!$stmt) {
        throw new Exception("Error al preparar búsqueda de lugar: " . $conn->error);
    }

    $stmt->bind_param("s", $nombre_lugar);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($fila = $resultado->fetch_assoc()) {
        return (int)$fila['id_lugar'];
    }

    $sqlInsertar = "
        INSERT INTO lugares (nombre_lugar, estado)
        VALUES (?, 'Activo')
    ";

    $stmt = $conn->prepare($sqlInsertar);

    if (!$stmt) {
        throw new Exception("Error al preparar inserción de lugar: " . $conn->error);
    }

    $stmt->bind_param("s", $nombre_lugar);
    $stmt->execute();

    return (int)$conn->insert_id;
}

?>