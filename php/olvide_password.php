<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Recuperar contraseña</title>
  <link rel="stylesheet" href="/sistema_ballet/css/estilos.css">

</head>
<body class="modo-claro">

<section class="panel-formulario" style="max-width: 420px; margin: 80px auto;">
  <form action="/sistema_ballet/php/enviar_recuperacion.php" method="POST">
    <h3>Recuperar contraseña</h3>

    <label for="correo">Correo</label>
    <input type="email" name="correo" id="correo" required>

    <button type="submit">Enviar enlace</button>

    <p style="margin-top:12px; text-align:center;">
      <a href="/sistema_ballet/php/login.php">Volver al inicio de sesión</a>
    </p>
  </form>
</section>

</body>
</html>