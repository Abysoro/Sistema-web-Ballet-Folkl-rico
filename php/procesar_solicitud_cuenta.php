<?php
include "conexion.php";
include "enviar_correo.php";


function mostrarMensajeSistema($titulo, $mensaje = "", $tipo = "info") {
    $clase = "mensaje-info";

    if ($tipo === "error") {
        $clase = "mensaje-error";
    } elseif ($tipo === "success") {
        $clase = "mensaje-success";
    } elseif ($tipo === "warning") {
        $clase = "mensaje-warning";
    }

    echo '
    <!DOCTYPE html>
    <html lang="es">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>' . htmlspecialchars($titulo, ENT_QUOTES, "UTF-8") . '</title>
      <link rel="stylesheet" href="../css/login.css">
      <style>
        body.login-page {
          margin: 0;
          min-height: 100vh;
          display: flex;
          align-items: center;
          justify-content: center;
          background: linear-gradient(90deg, #02111f 0%, #0a2f4b 55%, #214d6d 100%);
          font-family: Arial, sans-serif;
        }

        .mensaje-wrapper {
          width: 100%;
          max-width: 560px;
          padding: 24px;
        }

        .mensaje-panel {
          background: #031638;
          border: 1.5px solid #00e5ff;
          border-radius: 28px;
          padding: 36px 34px;
          box-shadow: 0 0 30px rgba(0, 229, 255, 0.12);
          color: white;
          text-align: center;
        }

        .mensaje-panel h2 {
          margin: 0 0 16px;
          color: #11f0ff;
          font-size: 2rem;
          letter-spacing: 1px;
        }

        .mensaje-panel p {
          margin: 0 0 28px;
          font-size: 1.05rem;
          line-height: 1.6;
          color: #e6f7ff;
        }

        .mensaje-info {
          border-left: 5px solid #00e5ff;
        }

        .mensaje-success {
          border-left: 5px solid #00d26a;
        }

        .mensaje-warning {
          border-left: 5px solid #ffb703;
        }

        .mensaje-error {
          border-left: 5px solid #ff4d6d;
        }

        .acciones-mensaje {
          display: flex;
          justify-content: center;
          gap: 14px;
          flex-wrap: wrap;
        }

        .btn-mensaje {
          display: inline-block;
          text-decoration: none;
          padding: 12px 22px;
          border-radius: 14px;
          font-weight: bold;
          font-size: 0.98rem;
          transition: 0.2s ease;
        }

        .btn-login {
          background: transparent;
          color: #11f0ff;
          border: 1.5px solid #11f0ff;
        }

        .btn-login:hover {
          background: rgba(17, 240, 255, 0.12);
        }

        .btn-secundario {
          background: #1f4060;
          color: white;
          border: 1px solid rgba(255,255,255,0.15);
        }

        .btn-secundario:hover {
          background: #2b567f;
        }
      </style>
    </head>
    <body class="login-page">
      <div class="mensaje-wrapper">
        <div class="mensaje-panel ' . $clase . '">
          <h2>' . htmlspecialchars($titulo, ENT_QUOTES, "UTF-8") . '</h2>
          <p>' . htmlspecialchars($mensaje, ENT_QUOTES, "UTF-8") . '</p>

          <div class="acciones-mensaje">
            <a href="/sistema_ballet/php/login.php" class="btn-mensaje btn-login">Volver al login</a>
            <a href="/sistema_ballet/php/solicitar_cuenta.php" class="btn-mensaje btn-secundario">Nueva solicitud</a>
          </div>
        </div>
      </div>
    </body>
    </html>
    ';
    exit;
}

$nombre = trim($_POST['nombre_completo'] ?? '');
$correo = trim($_POST['correo'] ?? '');
$password = $_POST['password'] ?? '';

$correoAutorizador = "aquazul.3el@gmail.com";

if ($nombre === '' || $correo === '' || $password === '') {
    mostrarMensajeSistema("Completa todos los campos.");
}

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    mostrarMensajeSistema("Correo inválido.");
}

if (strlen($password) < 6) {
    mostrarMensajeSistema("La contraseña debe tener al menos 6 caracteres.");
}

/* Verificar si el correo ya existe */
$stmt = $conn->prepare("SELECT id_usuario FROM usuario WHERE correo = ? LIMIT 1");
$stmt->bind_param("s", $correo);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    mostrarMensajeSistema("Este correo ya tiene una cuenta registrada.");
}

/* Verificar si ya tiene solicitud pendiente */
$stmt = $conn->prepare("SELECT id_solicitud FROM solicitud_usuario WHERE correo = ? AND estado = 'pendiente' LIMIT 1");
$stmt->bind_param("s", $correo);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    mostrarMensajeSistema(
    "Solicitud pendiente",
    "Este correo ya tiene una solicitud pendiente de aprobación. Por favor espera la autorización del administrador.",
    "info"
);
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);
$token = bin2hex(random_bytes(32));
$rol = "usuario";

$stmt = $conn->prepare("
    INSERT INTO solicitud_usuario 
    (nombre_completo, correo, password_hash, rol, token, estado)
    VALUES (?, ?, ?, ?, ?, 'pendiente')
");

$stmt->bind_param("sssss", $nombre, $correo, $passwordHash, $rol, $token);

if (!$stmt->execute()) {
    mostrarMensajeSistema("Error al registrar la solicitud: " . $conn->error);
}

/*
  En localhost, estos enlaces solo funcionarán en la computadora donde está XAMPP.
  Si luego subes el sistema a hosting, debes cambiar localhost por tu dominio.
*/
$linkAprobar = "http://localhost/sistema_ballet/php/responder_solicitud_cuenta.php?accion=aprobar&token=" . urlencode($token);
$linkRechazar = "http://localhost/sistema_ballet/php/responder_solicitud_cuenta.php?accion=rechazar&token=" . urlencode($token);

$mensaje = "
  <h2>Solicitud de nueva cuenta</h2>
  <p>Se ha solicitado crear una nueva cuenta para el sistema Sueño y Danza.</p>

  <p><strong>Nombre:</strong> {$nombre}</p>
  <p><strong>Correo:</strong> {$correo}</p>
  <p><strong>Rol solicitado:</strong> {$rol}</p>

  <hr>

  <p>Para autorizar esta cuenta, presiona:</p>
  <p>
    <a href='{$linkAprobar}' style='padding:10px 16px;background:#00b894;color:white;text-decoration:none;border-radius:6px;'>
      Aprobar cuenta
    </a>
  </p>

  <p>Para rechazar esta solicitud, presiona:</p>
  <p>
    <a href='{$linkRechazar}' style='padding:10px 16px;background:#d63031;color:white;text-decoration:none;border-radius:6px;'>
      Rechazar solicitud
    </a>
  </p>
";

$enviado = enviarCorreo($correoAutorizador, "Solicitud de nueva cuenta - Sueño y Danza", $mensaje);

if (!$enviado) {
    mostrarMensajeSistema("La solicitud fue registrada, pero no se pudo enviar el correo de autorización.");
}

mostrarMensajeSistema(
    "Solicitud enviada",
    "Tu solicitud fue enviada correctamente al administrador para su autorización.",
    "success"
);
$conn->close();
?>