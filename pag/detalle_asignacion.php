<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Detalle de Asignación</title>
  <link rel="stylesheet" href="../css/estilos.css">
  <link rel="stylesheet" href="../css/tema_claro.css">
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
 
<section class="seccion-tabla-unica">
  <h3>Historial detallado de asignaciones</h3>
 
  <div class="filtro-detalle">
    <label for="filtroCategoria">Filtrar por categoría:</label>
    <select id="filtroCategoria">
  <option value="todas">Todas</option>
  <option value="ninos">Niños</option>
  <option value="juvenil">Juvenil</option>
  <option value="aspirantes">Aspirantes</option>
  <option value="elenco oficial">Elenco Oficial</option>
</select>
  </div>
 <div class="acciones-detalle-asignacion">
  <button type="button" id="btnExportarHistorialAsignacion" class="btn-reporte-colas">
    <i class="fa-solid fa-file-pdf"></i>
    <span>Exportar historial PDF</span>
  </button>
</div>
<br>
  <div class="tabla-scroll">
  <table class="tabla-detalle-asignacion">
    <thead>
      <tr>
        <td>Integrante</td>
        <td>Apellido</td>
        <td>Presentación</td>
        <td>Categoría</td>
        <td>Ingreso a cola</td>
        <td>Fecha asignación</td>
        <td>Tiempo espera</td>
        <td>Estado</td>
        <td>Tipo asignación</td>
        <td>Motivo excepción</td>
        <td>Veces no asignado</td>
      </tr>
    </thead>
    <tbody id="tablaDetalleAsignacion"></tbody>
  </table>
</div>
</section>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>


<script src="../js/sidebar.js"></script>
<script src="../js/detalle_asignacion.js"></script>
</body>
</html>