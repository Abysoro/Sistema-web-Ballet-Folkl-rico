<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Asignación de Integrantes</title>

  <link rel="stylesheet" href="../css/estilos.css">
  <link rel="stylesheet" href="../css/tema_claro.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="icon" type="image/png" href="/sistema_ballet/img/logo.png">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>

  <header>
    <img src="../img/s1.png" class="logo" alt="Logo del sistema">
    <div class="header-text">
    <h1>Asignación de Integrantes a Presentaciones</h1>
     <p>Elenco de Danza — Modelo de Teoría de Colas</p>
    </div>
    <button id="themeToggle" class="theme-toggle" type="button" aria-label="Cambiar tema">
    <i class="fa-solid fa-moon"></i>
  </button>
  </header>


  <aside class="sidebar cerrado" id="sidebar">
  <button class="toggle-btn" id="toggleBtn" type="button" aria-label="Abrir menú">
    <i class="fa-solid fa-bars"></i>
  </button>

  <nav class="sidebar-menu">
    <a href="../index.php">
      <i class="fa-solid fa-house"></i>
      <span>Inicio</span>
    </a>

    <a href="integrantes.php">
      <i class="fa-solid fa-users"></i>
      <span>Registro de Integrantes</span>
    </a>

    <a href="presentaciones.php">
      <i class="fa-solid fa-music"></i>
      <span>Registro de Presentaciones</span>
    </a>

    
    <a href="auto.php">
      <i class="fa-solid fa-user-check"></i>
      <span>Asignación Inteligente</span>
    </a>

    <a href="detalle_asignacion.php">
      <i class="fa-solid fa-table"></i>
      <span>Detalle</span>
    </a>

    <a href="metricas_cola.php">
      <i class="fa-solid fa-chart-column"></i>
      <span>Métricas</span>
    </a>

    <a href="evaluacion_asignacion.php">
      <i class="fa-solid fa-clipboard-check"></i>
      <span>Evaluación</span>
    </a>

    <a href="cronograma_inteligente.php">
      <i class="fa-solid fa-calendar-days"></i>
      <span>Cronograma</span>
    </a>
  </nav>
</aside>

  <section class="contenedor-asignacion">

    <div class="panel-formulario">
      <h3>Seleccionar danza</h3>

      <div class="tabla-scroll tabla-presentaciones-scroll">
        <table class="tabla-presentaciones">
          <thead>
            <tr>
              <th>Fecha</th>
              <th>Danza</th>
              <th>Categoría</th>
              <th>Capacidad</th>
              <th>Elegir</th>
            </tr>
          </thead>
          <tbody id="tablaPresentaciones"></tbody>
        </table>
      </div>
    </div>

    <div class="panel-tabla panel-tabla-estrecha">
      <h3>Integrantes disponibles</h3>

      <div class="tabla-scroll">
        <table class="tabla-integrantes">
          <thead>
            <tr>
              <th>Nombre</th>
              <th>Apellido</th>
              <th>Categoría</th>
              <th>Nivel</th>
              <th>Asignar</th>
            </tr>
          </thead>

          <tbody id="tablaIntegrantesDisponibles"></tbody>
        </table>
      </div>
    </div>

  </section>

  <script src="../js/sidebar.js"></script>
  <script src="../js/asignacion.js"></script>

</body>
</html>