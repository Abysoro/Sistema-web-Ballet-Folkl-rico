<?php
session_start();

if (isset($_SESSION['id_usuario'])) {
    header("Location: /sistema_ballet/index.php");
    exit;
}

$errorLogin = $_SESSION['login_error'] ?? "";
unset($_SESSION['login_error']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Iniciar sesión</title>
  <link rel="stylesheet" href="../css/login.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="icon" type="image/png" href="/sistema_ballet/img/logo.png">
</head>
<body class="login-page">

  <div class="login-wrapper">

    <div class="login-left">
      <div class="login-bienvenida">
        <h1>BIENVENIDO</h1>
        <h2>AL ELENCO ARTÍSTICO</h2>
        <h3>"SUEÑO Y DANZA"</h3>
        <p>Ingresa tus credenciales para acceder al sistema</p>
      </div>

      <div class="login-arte">
        <img src="../img/salay.jpg" alt="Arte danza" class="arte-principal">
        <img src="../img/tinku.jpg" alt="Máscara decorativa" class="arte-secundario">
      </div>
    </div>

    <div class="login-right">
      <section class="login-panel">
        <form action="procesar_login.php" method="POST">
          <h3>Iniciar sesión</h3>
<?php if (!empty($errorLogin)): ?>
  <div class="login-alerta-error">
    <i class="fa-solid fa-circle-exclamation"></i>
    <span><?php echo htmlspecialchars($errorLogin, ENT_QUOTES, 'UTF-8'); ?></span>
  </div>
<?php endif; ?>
          <label for="correo">Correo</label>
          <input type="email" name="correo" id="correo" placeholder="Ingrese su correo" required>

          <label for="password">Contraseña</label>

        <div class="password-wrapper">
         <input 
            type="password" 
            name="password" 
            id="password" 
            placeholder="Ingrese su contraseña" 
            required
            >

        <button type="button" class="btn-ver-password" id="btnVerPassword">
           <i class="fa-solid fa-eye"></i>
        </button>
      </div>
          <button type="submit">Ingresar</button>
          
          <p class="login-link">
            <a href="/sistema_ballet/php/olvide_password.php">¿Olvidaste tu contraseña?</a>
          </p>
          <p class="login-link">
            <a href="/sistema_ballet/php/solicitar_cuenta.php">
              Solicitar nueva cuenta
            </a>
        </p>
        </form>
      </section>
    </div>

  </div>
<script>
  const passwordInput = document.getElementById("password");
  const btnVerPassword = document.getElementById("btnVerPassword");

  if (passwordInput && btnVerPassword) {
    btnVerPassword.addEventListener("click", function () {
      const icono = this.querySelector("i");

      if (passwordInput.type === "password") {
        passwordInput.type = "text";
        icono.classList.remove("fa-eye");
        icono.classList.add("fa-eye-slash");
      } else {
        passwordInput.type = "password";
        icono.classList.remove("fa-eye-slash");
        icono.classList.add("fa-eye");
      }
    });
  }
</script>
</body>
</html>