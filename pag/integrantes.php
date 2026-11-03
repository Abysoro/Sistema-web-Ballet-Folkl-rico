<?php include "../php/verificar_sesion.php"; ?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registro de Integrantes</title>
  <link rel="stylesheet" href="../css/tema_claro.css">
  <link rel="stylesheet" href="../css/estilos.css">
  <link rel="icon" type="image/png" href="/sistema_ballet/img/logo.png">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
 
 
<header>
  <img src="../img/s1.png" class="logo" alt="Logo del sistema">

  <div class="header-text">
    <h1>Registro de Integrantes</h1>
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


<section class="contenedor-principal">
 
  <div class="panel-formulario">
    <form id="formIntegrante">
      <h3>Registro de estudiantes</h3>
      <input type="hidden" id="id_integrante" name="id_integrante">
 
      <label>Nombre</label>
      <input type="text" name="nombre" id="nombre" required>
 
      <label>Apellido</label>
      <input type="text" name="apellido" id="apellido" required>
 
      <label>Categoría</label>
      <select name="categoria" id="categoria" required>
        <option value="1">Niños</option>
        <option value="2">Juvenil</option>
        <option value="3">Aspirantes</option>
        <option value="4">Elenco Oficial</option>
      </select>
 
      <label>Nivel</label>
      <select name="nivel" id="nivel" required>
        <option value="1">Básico</option>
        <option value="2">Intermedio</option>
        <option value="3">Avanzado</option>
      </select>
 
      <label>Rapidez de aprendizaje (1 a 5)</label>
      <select name="rapidez_aprendizaje" id="rapidez_aprendizaje" required>
        <option value="1">1 - Muy baja</option>
        <option value="2">2 - Baja</option>
        <option value="3" selected>3 - Media</option>
        <option value="4">4 - Alta</option>
        <option value="5">5 - Muy alta</option>
      </select>
 
      <label>Años de antigüedad</label>
      <input type="number" name="anios_antiguedad" id="anios_antiguedad" min="0" value="0" required>
 
      <label>Cantidad de presentaciones previas</label>
      <input type="number" name="cantidad_presentaciones" id="cantidad_presentaciones" min="0" value="0" required>
 
      <label>Puntaje de disciplina (1 a 5)</label>
      <select name="puntaje_disciplina" id="puntaje_disciplina" required>
        <option value="1">1 - Muy baja</option>
        <option value="2">2 - Baja</option>
        <option value="3" selected>3 - Media</option>
        <option value="4">4 - Alta</option>
        <option value="5">5 - Muy alta</option>
      </select>
 
      <label>Puntaje de responsabilidad (1 a 5)</label>
      <select name="puntaje_responsabilidad" id="puntaje_responsabilidad" required>
        <option value="1">1 - Muy baja</option>
        <option value="2">2 - Baja</option>
        <option value="3" selected>3 - Media</option>
        <option value="4">4 - Alta</option>
        <option value="5">5 - Muy alta</option>
      </select>
 
      <button>
        <i class="fa-solid fa-user-plus"></i>
        <span>Registrar Integrante</span>
      </button>
    </form>
  </div>
 
  <div class="panel-tabla">
  <h3>Integrantes registrados</h3>

  <!-- FILTROS DE BÚSQUEDA -->
  <div class="filtros-integrantes">

    <div class="filtro-item">
      <label for="buscarIntegrante">Buscar por nombre o apellido</label>
      <input
        type="search"
        id="buscarIntegrante"
        placeholder="Escriba un nombre o apellido..."
        autocomplete="off"
      >
    </div>

    <div class="filtro-item">
      <label for="filtroCategoriaIntegrantes">Categoría</label>
      <select id="filtroCategoriaIntegrantes">
        <option value="">Todas las categorías</option>
        <option value="Niños">Niños</option>
        <option value="Juvenil">Juvenil</option>
        <option value="Aspirantes">Aspirantes</option>
        <option value="Elenco Oficial">Elenco Oficial</option>
      </select>
    </div>

    <div class="filtro-item">
      <label for="filtroNivelIntegrantes">Nivel</label>
      <select id="filtroNivelIntegrantes">
        <option value="">Todos los niveles</option>
        <option value="Básico">Básico</option>
        <option value="Intermedio">Intermedio</option>
        <option value="Avanzado">Avanzado</option>
      </select>
    </div>

  </div>

  <div class="tabla-scroll">
      <table>
        <thead>
  <tr>
    <th>Nombre</th>
    <th>Apellido</th>
    <th>Categoría</th>
    <th>Nivel</th>
    <th>Rapidez</th>
    <th>Antigüedad</th>
    <th>Presentaciones <p>previas</p></th>
    <th>Disciplina</th>
    <th>Responsabilidad</th>
    <th>Acciones</th>
  </tr>
</thead>
<tbody id="tablaIntegrantes"></tbody>
      </table>
    </div>

    <!-- PAGINACIÓN -->
    <div class="paginacion-integrantes">

      <span id="infoPaginacionIntegrantes">
        Mostrando 0 de 0 integrantes
      </span>

      <div class="paginacion-controles">

        <button type="button" id="btnAnteriorIntegrantes">
          <i class="fa-solid fa-chevron-left"></i>
          Anterior
        </button>

        <span id="paginaActualIntegrantes">
          Página 1 de 1
        </span>

        <button type="button" id="btnSiguienteIntegrantes">
          Siguiente
          <i class="fa-solid fa-chevron-right"></i>
        </button>

      </div>

    </div>

  </div>

</section>
<script src="../js/sidebar.js"></script>
<script src="../js/app.js"></script>
</body>
</html>
