<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Evaluación de Asignación</title>
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
    <h1>Evaluación de Asignación Inteligente</h1>
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

<main class="contenedor-evaluacion">
 
  <section class="panel-acciones">

    <button id="btnEjecutar">
        <i class="fa-solid fa-play"></i>
        <span>Ejecutar asignación inteligente</span>
      </button>
    
    <button id="btnActualizar">
      <i class="fa-solid fa-rotate-right"></i>
      <span>Actualizar tabla</span> 
    </button>

    <button type="button" id="btnExportarPDF">
    <span>Exportar PDF</span>
  </button>

  <button type="button" id="btnVerSaturacion" class="btn-detalle">
  <i class="fa-solid fa-triangle-exclamation"></i>
  Ver saturación
</button>
  </section>
 
  <section class="panel-tabla">
    <h2>Resultados de evaluación</h2>
 
    <div class="filtro-detalle" style="margin-bottom: 20px;">
      <label for="filtroCategoria">Filtrar por categoría:</label>
      <select id="filtroCategoria">
        <option value="Todas">Todas</option>
      </select>
    </div>
 
    <div class="tabla-scroll">
      <table class="tabla-evaluacion">
  <thead>
    <tr>
      <th>Presentación</th>
      <th>Integrante</th>
      <th>Categoría</th>
      <th>Detalle</th>
    </tr>
  </thead>
      <tbody id="tablaEvaluacion"></tbody>
      </table>
    </div>
  </section>
 
</main>

<div class="modal-overlay" id="modalDetalle">
  <div class="modal-contenido">
    <div class="modal-header">
      <h3>Detalle de evaluación</h3>
      <button type="button" class="cerrar-modal" id="cerrarModal">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>


    <div class="modal-body" id="contenidoDetalleModal"></div>
  </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>

<script src="../js/sidebar.js"></script>
<script src="../js/evaluacion_asignacion.js"></script>
</body>


</div>
</html>
 