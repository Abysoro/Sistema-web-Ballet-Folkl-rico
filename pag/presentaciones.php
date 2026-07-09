<?php include "../php/verificar_sesion.php"; ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registro de Presentaciones</title>
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
    <h1>Registro de Presentaciones</h1>
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
 
<section class="contenedor-principal">
 
  <div class="panel-formulario">
    <form id="formPresentacion">
      <h3>Registro de Presentaciones</h3>
      <input type="hidden" name="id_presentacion" id="id_presentacion">
 
      <label for="nombre_presentacion">Nombre de la presentación</label>
      <input type="text" name="nombre_presentacion" id="nombre_presentacion" required>
 
      <label for="categoria">Categoría</label>
      <select name="categoria" id="categoria" required>
        <option value="1">Niños</option>
        <option value="2">Juvenil</option>
        <option value="3">Aspirantes</option>
        <option value="4">Elenco Oficial</option>
      </select>
 
      <label for="capacidad">Capacidad</label>
      <input type="number" id="capacidad" name="capacidad" min="1" step="1" value="1">
 
      <label>Nivel mínimo</label>
      <select name="nivel_minimo" id="nivel_minimo" required>
        <option value="1">Básico</option>
        <option value="2">Intermedio</option>
        <option value="3">Avanzado</option>
      </select>
 
      <label for="fecha">Fecha</label>
      <input type="date" name="fecha" id="fecha" required>

      <label for="tipo_evento">Tipo de evento</label>
  <select name="tipo_evento" id="tipo_evento" required>
  <option value="">Seleccione un tipo</option>
  </select>

<div id="contenedor_otro_tipo" style="display:none;">
  <label for="otro_tipo_evento">Especifique el tipo</label>
  <input type="text" name="otro_tipo_evento" id="otro_tipo_evento">
</div>
 
      <label for="lugar">Lugar Específico</label>
      <input type="text" name="lugar" id="lugar" required>

      <label for="jornada">Seleccionar una presentación</label>
      <select id="jornada" name="jornada">
        <option value="">Seleccione una presentación</option>
      </select>

  <button type="button" id="btnNuevaJornada" class="btn-secundario">
    Nueva Presentación
    </button>
    <label for="duracion_minutos">Duración de la danza (minutos)</label>
      <input type="number" id="duracion_minutos" name="duracion_minutos" min="1" step="1" value="1">
   


      
  <button type="submit">
        <i class="fa-solid fa-plus"></i>
        <span>Registrar Presentación</span>
      </button>
     
    </form>
  </div>
 
  <div class="panel-tabla">
    <h3>Presentaciones registradas</h3>
    <div class="acciones-panel-tabla">
  <button type="button" id="btnMostrarHistorial" class="btn-historial-evento">
    <i class="fa-solid fa-clock-rotate-left"></i>
    <span>Historial por tipo</span>
  </button>
</div>
    <div class="tabla-scroll">
  
      <table>
        <thead>
          <tr>
            <th>Nombre</th>
            <th>Tipo</th>
            <th>Categoría</th>
            <th>Capacidad</th>
            <th>Nivel mínimo</th>
            <th>Fecha</th>
            <th>Lugar</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody id="tablaPresentaciones"></tbody>
      </table>
    </div>
  </div>
 
</section>

<script>
  window.LOGO_PDF_URL = "../img/s1.png";
</script>
<script src="../js/sidebar.js"></script>
<script src="../js/presentaciones.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
</body>
</html>