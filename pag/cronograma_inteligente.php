<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Optimización de Cronograma</title>
  <link rel="stylesheet" href="../css/estilos.css">
  <link rel="stylesheet" href="../css/tema_claro.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="icon" type="image/png" href="/sistema_ballet/img/logo.png">
</head>
<body>

  <header>
  <img src="../img/s1.png" class="logo" alt="Logo del sistema">
  <div class="header-text">
    <h1>Optimización de Cronograma</h1>
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

  <main class="contenedor">
    
    <section class="selector-jornada-lineal">
  <div class="titulo-selector-jornada">
    <i class="fa-solid fa-filter"></i>
   <h2><span>Seleccionar Jornada</span></h2>
  </div>
<br>
<p></p>
  <div class="grupo grupo-jornada">
    <label for="filtroJornada">Jornada</label>
    <select id="filtroJornada">
      <option value="">Seleccione una jornada</option>
    </select>
  </div>

  <div class="grupo grupo-fecha-jornada">
    <label for="fechaJornadaSeleccionada">Fecha de la jornada</label>
    <input type="text" id="fechaJornadaSeleccionada" readonly placeholder="Se cargará automáticamente">
  </div>
</section>

<div class="acciones-cronograma-externas">
  <button type="button" id="btnCargar">
    <i class="fa-solid fa-magnifying-glass"></i>
    <span>Cargar</span>
  </button>

  <button type="button" id="btnOptimizar">
    <i class="fa-solid fa-shuffle"></i>
    <span>Optimizar Cronograma</span>
  </button>

  <button type="button" id="btnGuardar">
    <i class="fa-solid fa-floppy-disk"></i>
    <span>Guardar Cronograma</span>
  </button>
</div>


    <section class="card">
      <h2><i class="fa-solid fa-list"></i> Presentaciones de la Jornada</h2>
      <div class="tabla-responsive">
        <table>
          <thead>
            <tr>
              <th>Presentación</th>
              <th>Categoría</th>
              <th>Duración</th>
              <th>Capacidad</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody id="tablaPresentaciones"></tbody>
        </table>
      </div>
    </section>

    

    <section class="card">
      <h2><i class="fa-solid fa-wand-magic-sparkles"></i> Orden Optimizado</h2>
      <div class="tabla-responsive">
        <table>
          <thead>
            <div class="acciones-cronograma-externas">
            <button id="btnVerResumenCronograma" class="btn-resumen" type="button" style="display:none;" onclick="openResumenModal()">
             <i class="fa-solid fa-circle-check"></i> Ver estado del cronograma
            </button>
            </div>
            <tr>
              <th>Orden</th>
              <th>Presentación</th>
              <th>Hora Inicio</th>
              <th>Hora Fin</th>
              <th>Conflictos</th>
              <th>Observación</th>
            </tr>
          </thead>
          <tbody id="tablaCronograma"></tbody>
        </table>
      </div>

      
    </section>

    <section class="card">
      <h2><i class="fa-solid fa-triangle-exclamation"></i> Conflictos Detectados</h2>
      <div class="tabla-responsive">
        <table>
          <thead>
            <tr>
              <th>Integrante</th>
              <th>Presentación 1</th>
              <th>Presentación 2</th>
              <th>Min. requeridos</th>
              <th>Min. disponibles</th>
              <th>Estado</th>
            </tr>
          </thead>
          <tbody id="tablaConflictos"></tbody>
        </table>
      </div>
    </section>

  </main>

  <div id="modalIntegrantes" class="modal">
  <div class="modal-contenido">
    <span class="cerrar" onclick="closeModal('modalIntegrantes')">&times;</span>
    <h2>Integrantes de la presentación</h2>
    <div id="contenidoIntegrantes"></div>
  </div>
</div>

<div id="modalResumenCronograma" class="modal">
  <div class="modal-contenido modal-contenido-resumen">
    <span class="cerrar" onclick="closeModal('modalResumenCronograma')">&times;</span>
    <h2><i class="fa-solid fa-circle-check"></i> Estado del Cronograma</h2>
    <div id="contenidoResumenCronograma"></div>
  </div>
</div>

  <script src="../js/cronograma_inteligente.js"></script>
  <script src="../js/sidebar.js"></script>
</body>
</html>