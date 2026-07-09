<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Detalle de Asignación</title>
<link rel="stylesheet" href="../css/tema_claro.css">
<link rel="stylesheet" href="../css/estilos.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="icon" type="image/png" href="/sistema_ballet/img/logo.png">
</head>

<body>

<header>
    <img src="../img/s1.png" class="logo" alt="Logo del sistema">
  <div class="header-text">
    <h1>Detalle de Asignación</h1>
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
      <i class="fa-solid fa-car"></i>
      <span>Auto</span>
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


<section class="contenedor-form">

<h3>Historial detallado de asignaciones</h3>

<table border="1" width="100%">
    <thead>
        <tr>
            <th>Integrante</th>
            <th>Apellido</th>
            <th>Presentación</th>
            <th>Categoría</th>
            <th>Ingreso a cola</th>
            <th>Fecha asignación</th>
            <th>Tiempo espera</th>
            <th>Estado</th>
            <th>Veces no asignado</th>
        </tr>
    </thead>
    <tbody id="tablaDetalleAsignacion"></tbody>
</table>

</section>

<script src="../js/sidebar.js"></script>
<script src="../js/detalle_asignacion.js"></script>

</body>
</html>