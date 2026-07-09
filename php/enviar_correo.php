<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/*
  Como este archivo está en:
  sistema_ballet/php/enviar_correo.php

  Y PHPMailer está en:
  sistema_ballet/PHPMailer/src/

  entonces usamos ../PHPMailer/src/
*/

require __DIR__ . '/../PHPMailer/src/Exception.php';
require __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require __DIR__ . '/../PHPMailer/src/SMTP.php';

function enviarCorreo($destino, $asunto, $contenidoHtml) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;

        /*
          Usa el correo que enviará las autorizaciones.
          IMPORTANTE:
          En Gmail no uses tu contraseña normal.
          Debes usar una "Contraseña de aplicación".
        */
        $mail->Username = 'aquazul.3el@gmail.com';
        $mail->Password = 'j s e n a z v v m q c k y o a n';

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->CharSet = 'UTF-8';

        $mail->setFrom('aquazul.3el@gmail.com', 'Sistema Sueño y Danza');
        $mail->addAddress($destino);

        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body = $contenidoHtml;

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log("Error al enviar correo: " . $mail->ErrorInfo);
        return false;
    }
}
?>