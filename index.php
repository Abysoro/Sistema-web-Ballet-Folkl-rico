<?php
session_start();

if (!isset($_SESSION['id_usuario'])) {
    header("Location: /sistema_ballet/php/login.php");
    exit;
}

$nombreUsuario = $_SESSION['nombre_usuario'] ?? "Usuario";
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sistema de Gestión de Elenco</title>
  <link rel="stylesheet" href="css/estilos.css">
  <link rel="stylesheet" href="css/tema_claro.css">
  <link rel="stylesheet" href="css/login.css">
  <link rel="icon" type="image/png" href="/sistema_ballet/img/logo.png">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>

  <header>
    <img src="img/s1.png" class="logo" alt="Logo del sistema">
    <div class="header-text">
     <h1>Bienvenido, <?php echo htmlspecialchars($nombreUsuario, ENT_QUOTES, 'UTF-8'); ?></h1>
    </div>
     <a href="/sistema_ballet/php/logout.php" class="btn-logout">
    <i class="fa-solid fa-right-from-bracket"></i>
    <span>Cerrar sesión</span>
  </a>
    <button id="themeToggle" class="theme-toggle" type="button" aria-label="Cambiar tema">
    <i class="fa-solid fa-moon"></i>
  </button>
  </header>

  <aside class="sidebar cerrado" id="sidebar">
  <button class="toggle-btn" id="toggleBtn" type="button" aria-label="Abrir menú">
    <i class="fa-solid fa-bars"></i>
  </button>

  <nav class="sidebar-menu">
    <a href="index.php" class="active">
      <i class="fa-solid fa-house"></i>
      <span>Inicio</span>
    </a>

    <a href="pag/integrantes.php">
      <i class="fa-solid fa-users"></i>
      <span>Registro de Integrantes</span>
    </a>

    <a href="pag/presentaciones.php">
      <i class="fa-solid fa-music"></i>
      <span>Registro de Presentaciones</span>
    </a>


    <a href="pag/auto.php">
      <i class="fa-solid fa-user-check"></i>
      <span>Asignación Inteligente</span>
    </a>

    <a href="pag/detalle_asignacion.php">
      <i class="fa-solid fa-table"></i>
      <span>Detalle</span>
    </a>

    <a href="pag/metricas_cola.php">
      <i class="fa-solid fa-chart-column"></i>
      <span>Métricas</span>
    </a>

    <a href="pag/evaluacion_asignacion.php">
      <i class="fa-solid fa-clipboard-check"></i>
      <span>Evaluación</span>
    </a>
    
    <a href="pag/cronograma_inteligente.php">
      <i class="fa-solid fa-calendar-days"></i>
      <span>Cronograma</span>
    </a>
  </nav>
</aside>

  <main class="contenido-principal">
  <div class="dashboard-inicio">

    <div class="dashboard-dos-columnas">

      <!-- PANEL DE CONTROL -->
      <section class="panel-dashboard">
  <h2>Panel de control</h2>

  <div class="tarjetas-resumen">

    <div class="tarjeta-resumen card-total-integrantes" id="cardTotalIntegrantes">
      <div id="contenidoTotalIntegrantes">
        <h3>Total de Integrantes</h3>
        <span><p id="totalIntegrantes">0</p></span>
        Registrados en el sistema
      </div>
    </div>

    <div class="tarjeta-resumen">
      <h3>Total de Presentaciones</h3>
      <p id="totalPresentaciones">0</p>
      <span>Disponibles actualmente</span>
    </div>

    <div class="tarjeta-resumen">
      <h3>Asignados</h3>
      <p id="totalAsignados">0</p>
      <span>Integrantes con asignación</span>
    </div>

    <div class="tarjeta-resumen">
      <h3>En Espera</h3>
      <p id="totalEspera">0</p>
      <span>Integrantes pendientes</span>
    </div>

  </div>
</section>

      <!-- ESTADO ACTUAL + AVISOS -->
      <section class="estado-actual-dashboard">
        <div class="bloque-dashboard">
          <h2>Estado actual</h2>

          <div class="contenido-estado">
            <div class="item-estado">
              <span class="etiqueta">Próxima presentación</span>
              <strong id="proximaPresentacion">--</strong>
            </div>

            <div class="item-estado">
              <span class="etiqueta">Fecha</span>
              <strong id="fechaPresentacion">--</strong>
            </div>

            <div class="item-estado">
              <span class="etiqueta">Lugar</span>
              <strong id="lugarPresentacion">--</strong>
            </div>

            <div class="item-estado">
              <span class="etiqueta">Categoría</span>
              <strong id="categoriaPresentacion">--</strong>
            </div>

            <div class="item-estado">
              <span class="etiqueta">Cupos disponibles</span>
              <strong id="cuposDisponibles">0</strong>
            </div>

            <div class="item-estado">
              <span class="etiqueta">Nivel de ocupación</span>
              <strong id="ocupacionPresentacion">--</strong>
            </div>
          </div>
        </div>

        <div class="bloque-dashboard">
          <h2>Avisos</h2>

          <div class="lista-avisos">
            <div class="aviso aviso-alerta">
              <span class="icono-aviso">
                <i class="fa-solid fa-triangle-exclamation"></i>
              </span>
              <div>
                <strong id="avisoSinAsignarTitulo">0 integrantes sin asignar</strong>
                <p id="avisoSinAsignarTexto">Requieren revisión para futuras presentaciones.</p>
              </div>
            </div>

            <div class="aviso aviso-info">
              <span class="icono-aviso">
                <i class="fa-solid fa-circle-info"></i>
              </span>
              <div>
                <strong id="avisoLimitadasTitulo">0 presentaciones con capacidad limitada</strong>
                <p id="avisoLimitadasTexto">Podrían requerir reorganización de integrantes.</p>
              </div>
            </div>

            <div class="aviso aviso-neon">
              <span class="icono-aviso">
                <i class="fa-solid fa-chart-line"></i>
              </span>
              <div>
                <strong id="avisoDemandaTitulo">Categoría con mayor demanda: --</strong>
                <p id="avisoDemandaTexto">Se recomienda revisar la disponibilidad del elenco.</p>
              </div>
            </div>
          </div>
        </div>
      </section>

    </div>

  </div>
</main>

  <script src="js/app.js"></script>
  <script src="js/sidebar.js"></script>
</body>
</html>