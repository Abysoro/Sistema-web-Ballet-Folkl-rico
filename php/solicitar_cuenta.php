<?php
session_start();

if (isset($_SESSION['id_usuario'])) {
    header("Location: /sistema_ballet/index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Solicitar nueva cuenta</title>
  <link rel="stylesheet" href="../css/login2.css">
  <link rel="stylesheet" href="../css/estilos.css">
</head>

<body class="login-page solicitud-page">

  <div class="solicitud-contenedor">

    <section class="login-panel solicitud-panel">

      <div class="solicitud-logo-box">
        <img src="../img/s1.png" alt="Logo Sueño y Danza" class="solicitud-logo">
      </div>

      <form action="procesar_solicitud_cuenta.php" method="POST">
        <h3>Solicitar nueva cuenta</h3>

        <label for="nombre_completo">Nombre completo</label>
        <input 
          type="text" 
          name="nombre_completo" 
          id="nombre_completo" 
          placeholder="Ingrese su nombre completo" 
          required
        >

        <label for="correo">Correo</label>
        <input 
          type="email" 
          name="correo" 
          id="correo" 
          placeholder="Ingrese su correo" 
          required
        >

        <label for="password">Contraseña</label>
        <input 
          type="password" 
          name="password" 
          id="password" 
          placeholder="Cree una contraseña" 
          required 
          minlength="6"
        >

        <button type="submit">Enviar solicitud</button>

        <p class="login-link">
          <a href="/sistema_ballet/php/login.php">Volver al inicio de sesión</a>
        </p>
      </form>

    </section>

  </div>

</body>
</html>