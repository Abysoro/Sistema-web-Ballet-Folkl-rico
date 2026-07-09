<?php include "../php/verificar_sesion.php"; ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Métricas de Cola</title>
  <link rel="stylesheet" href="../css/estilos.css">
  <link rel="stylesheet" href="../css/tema_claro.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="icon" type="image/png" href="/sistema_ballet/img/logo.png">
</head>
<body>
 
<header>
  <img src="../img/s1.png" class="logo" alt="Logo del sistema">
  <div class="header-text">
    <h1>Métricas del Sistema de Cola</h1>
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

 

<div class="acciones-reporte-colas">
  <button type="button" id="btnReporteTeoriaColas" class="btn-reporte-colas">
    <i class="fa-solid fa-file-pdf"></i>
    <span>Reporte de Colas</span>
  </button>
</div>

<section class="seccion-tabla-unica seccion-colas-principal">
  <h3>
    <i class="fa-solid fa-table-list"></i>
    Resultados del cálculo de teoría de colas
  </h3>
 
  <div class="tabla-scroll">
    <table>
      <thead>
        <tr>
          <th>Presentación</th>
          <th>Tasa de llegada</th>
          <th>Tasa de servicio</th>
          <th>Longitud de cola</th>
          <th>Tiempo espera promedio</th>
          <th>Utilización del sistema</th>
        </tr>
      </thead>
      <tbody id="tablaMetricasCola"></tbody>
    </table>
  </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>

<script src="../js/sidebar.js"></script>
<script src="../js/metricas_cola.js"></script>
</body>
</html>