<?php
include "conexion.php";
include "enviar_correo.php";

$accion = $_GET['accion'] ?? '';
$token = $_GET['token'] ?? '';

if ($accion !== 'aprobar' && $accion !== 'rechazar') {
    die("Acción inválida.");
}

if ($token === '') {
    die("Token inválido.");
}

$stmt = $conn->prepare("
    SELECT *
    FROM solicitud_usuario
    WHERE token = ?
    LIMIT 1
");
$stmt->bind_param("s", $token);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    die("Solicitud no encontrada.");
}

$solicitud = $res->fetch_assoc();

if ($solicitud['estado'] !== 'pendiente') {
    die("Esta solicitud ya fue procesada.");
}

$idSolicitud = (int)$solicitud['id_solicitud'];
$nombre = $solicitud['nombre_completo'];
$correo = $solicitud['correo'];
$passwordHash = $solicitud['password_hash'];
$rol = $solicitud['rol'];

if ($accion === 'rechazar') {
    $stmt = $conn->prepare("
        UPDATE solicitud_usuario
        SET estado = 'rechazado', fecha_respuesta = NOW()
        WHERE id_solicitud = ?
    ");
    $stmt->bind_param("i", $idSolicitud);
    $stmt->execute();

    $mensajeUsuario = "
      <h2>Solicitud rechazada</h2>
      <p>Tu solicitud de cuenta para el sistema Sueño y Danza fue rechazada por administración.</p>
    ";

    enviarCorreo($correo, "Solicitud de cuenta rechazada", $mensajeUsuario);

    echo "
      <h2>Solicitud rechazada correctamente</h2>
      <p>Se notificó al solicitante.</p>
    ";

    exit;
}

/* Aprobar solicitud */

/* Verificar que no se haya creado mientras tanto */
$stmt = $conn->prepare("SELECT id_usuario FROM usuario WHERE correo = ? LIMIT 1");
$stmt->bind_param("s", $correo);
$stmt->execute();
$resUsuario = $stmt->get_result();

if ($resUsuario->num_rows > 0) {
    $stmt = $conn->prepare("
        UPDATE solicitud_usuario
        SET estado = 'rechazado', fecha_respuesta = NOW()
        WHERE id_solicitud = ?
    ");
    $stmt->bind_param("i", $idSolicitud);
    $stmt->execute();

    die("Este correo ya tiene una cuenta registrada.");
}

/* Crear usuario */
$stmt = $conn->prepare("
    INSERT INTO usuario
    (nombre_completo, correo, password_hash, rol, estado)
    VALUES (?, ?, ?, ?, 'activo')
");
$stmt->bind_param("ssss", $nombre, $correo, $passwordHash, $rol);

if (!$stmt->execute()) {
    die("Error al crear el usuario: " . $conn->error);
}

/* Marcar solicitud como aprobada */
$stmt = $conn->prepare("
    UPDATE solicitud_usuario
    SET estado = 'aprobado', fecha_respuesta = NOW()
    WHERE id_solicitud = ?
");
$stmt->bind_param("i", $idSolicitud);
$stmt->execute();

/* Notificar al solicitante */
$linkLogin = "http://localhost/sistema_ballet/php/login.php";

$mensajeUsuario = "
  <h2>Cuenta aprobada</h2>
  <p>Tu cuenta para el sistema Sueño y Danza fue aprobada.</p>
  <p>Ya puedes iniciar sesión con el correo y la contraseña que registraste.</p>
  <p>
    <a href='{$linkLogin}' style='padding:10px 16px;background:#00b894;color:white;text-decoration:none;border-radius:6px;'>
      Iniciar sesión
    </a>
  </p>
";

enviarCorreo($correo, "Tu cuenta fue aprobada - Sueño y Danza", $mensajeUsuario);

echo "
  <h2>Cuenta aprobada correctamente</h2>
  <p>El usuario {$nombre} ya puede iniciar sesión.</p>
";

$conn->close();
?>