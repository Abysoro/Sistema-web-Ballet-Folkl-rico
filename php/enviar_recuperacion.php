<?php
include "conexion.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../PHPMailer/src/Exception.php';
require __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require __DIR__ . '/../PHPMailer/src/SMTP.php';

$correo = trim($_POST['correo'] ?? '');

if ($correo === '') {
    die("Debes ingresar un correo.");
}

$stmt = $conn->prepare("SELECT id_usuario, correo, estado FROM usuario WHERE correo = ? LIMIT 1");
$stmt->bind_param("s", $correo);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    die("No existe una cuenta con ese correo.");
}

$usuario = $res->fetch_assoc();

if ($usuario['estado'] !== 'activo') {
    die("La cuenta no está activa.");
}

$id_usuario = (int)$usuario['id_usuario'];

/* generar token */
$token = bin2hex(random_bytes(32));
$token_hash = hash('sha256', $token);
$expira_en = date("Y-m-d H:i:s", strtotime("+30 minutes"));

/* borrar tokens anteriores del mismo usuario */
$stmtDelete = $conn->prepare("DELETE FROM recuperacion_password WHERE id_usuario = ?");
$stmtDelete->bind_param("i", $id_usuario);
$stmtDelete->execute();

/* guardar nuevo token */
$stmtInsert = $conn->prepare("INSERT INTO recuperacion_password (id_usuario, token_hash, expira_en, usado) VALUES (?, ?, ?, 0)");
$stmtInsert->bind_param("iss", $id_usuario, $token_hash, $expira_en);
$stmtInsert->execute();

/* enlace de recuperación */
$enlace = "http://localhost/sistema_ballet/php/reset_password.php?token=" . urlencode($token);

/* enviar correo */
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'aquazul.3el@gmail.com';
    $mail->Password   = 'j s e n a z v v m q c k y o a n';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->CharSet    = 'UTF-8';

    $mail->setFrom('TU_CORREO_EMISOR@gmail.com', 'Sistema Ballet');
    $mail->addAddress($correo);

    $mail->isHTML(true);
    $mail->Subject = 'Recuperación de contraseña';
    $mail->Body = "
        <h2>Recuperación de contraseña</h2>
        <p>Haz clic en el siguiente enlace para restablecer tu contraseña:</p>
        <p><a href='$enlace'>$enlace</a></p>
        <p>Este enlace vence en 30 minutos.</p>
    ";
    $mail->AltBody = "Usa este enlace para restablecer tu contraseña: $enlace";

    $mail->send();

    echo "Se envió el enlace de recuperación a tu correo.";
} catch (Exception $e) {
    echo "No se pudo enviar el correo. Error: " . $mail->ErrorInfo;
}

$conn->close();
?>