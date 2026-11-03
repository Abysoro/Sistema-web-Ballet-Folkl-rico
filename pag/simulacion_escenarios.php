<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Simulación de Escenarios</title>
  <link rel="stylesheet" href="../css/estilos.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<header>
  <img src="../img/s1.png" class="logo" alt="Logo del sistema">
  <div class="header-text">
    <h1>Simulación de Escenarios</h1>
    <p>Elenco de Danza — Análisis del flujo de atención</p>
  </div>
</header>

<aside class="sidebar cerrado" id="sidebar">
  <button class="toggle-btn" id="toggleBtn" type="button" aria-label="Abrir menú">
    <i class="fa-solid fa-bars"></i>
  </button>

  <nav class="sidebar-menu">
    <a href="../index.html">
      <i class="fa-solid fa-house"></i>
      <span>Inicio</span>
    </a>

    <a href="integrantes.html">
      <i class="fa-solid fa-users"></i>
      <span>Registro de Integrantes</span>
    </a>

    <a href="presentaciones.html">
      <i class="fa-solid fa-music"></i>
      <span>Registro de Presentaciones</span>
    </a>

    <a href="auto.php">
      <i class="fa-solid fa-user-check"></i>
      <span>Asignación Inteligente</span>
    </a>

    <a href="detalle_asignacion.html">
      <i class="fa-solid fa-table"></i>
      <span>Detalle</span>
    </a>

    <a href="metricas_cola.html">
      <i class="fa-solid fa-chart-column"></i>
      <span>Métricas</span>
    </a>

    <a href="evaluacion_asignacion.html">
      <i class="fa-solid fa-clipboard-check"></i>
      <span>Evaluación</span>
    </a>

    <a href="simulacion_escenarios.html">
      <i class="fa-solid fa-arrows-rotate"></i>
      <span>Simulación</span>
    </a>
  </nav>
</aside>

<section class="seccion-tabla-unica">
  <h3>Simulación de escenarios y análisis del flujo de atención</h3>

  <div class="contenedor-botones-simulacion" style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:20px;">
    <button type="button" class="btn" onclick="simularEscenario(1)">Escenario 1: Demanda baja</button>
    <button type="button" class="btn" onclick="simularEscenario(2)">Escenario 2: Alta demanda</button>
    <button type="button" class="btn" onclick="simularEscenario(3)">Escenario 3: Prioridad por nivel</button>
    <button type="button" class="btn" onclick="simularEscenario(4)">Escenario 4: Prioridad por equidad</button>
  </div>

  <div id="resumenSimulacion" class="resumen-simulacion" style="margin-bottom:20px;"></div>

  <div class="tabla-scroll">
    <table class="tabla-detalle-asignacion">
      <thead>
        <tr>
          <th>Integrante</th>
          <th>Resultado</th>
          <th>Motivo</th>
        </tr>
      </thead>
      <tbody id="tablaSimulacion"></tbody>
    </table>
  </div>
</section>

<script src="../js/sidebar.js"></script>
<script src="../js/simulacion_escenarios.js"></script>
</body>
</html>