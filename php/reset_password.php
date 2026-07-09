<?php
$token = $_GET['token'] ?? '';

if ($token === '') {
    die("Token no válido.");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Restablecer contraseña</title>
  <link rel="stylesheet" href="/sistema_ballet/css/estilos.css">
</head>
<body class="modo-claro">

<section class="panel-formulario" style="max-width: 420px; margin: 80px auto;">
  <form action="/sistema_ballet/php/actualizar_password.php" method="POST">
    <h3>Nueva contraseña</h3>

    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

    <label for="password">Nueva contraseña</label>
    <input type="password" name="password" id="password" required>

    <label for="password2">Confirmar contraseña</label>
    <input type="password" name="password2" id="password2" required>

    <button type="submit">Actualizar contraseña</button>
  </form>
</section>

</body>
</html>