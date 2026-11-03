let mostrandoCategoriasIntegrantes = false;
let totalIntegrantesGeneralDashboard = 0;

/* ==========================================
   FILTROS Y PAGINACIÓN DE INTEGRANTES
========================================== */

let paginaListadoIntegrantes = 1;
let totalPaginasListadoIntegrantes = 1;

const integrantesPorPagina = 10;

document.addEventListener("DOMContentLoaded", function () {
  if (document.getElementById("totalIntegrantes")) {
    cargarDashboardInicio();
    activarCardCategoriasDashboard();
  }

  const inputNombre = document.getElementById("nombre");
  const inputApellido = document.getElementById("apellido");
  const form = document.getElementById("formIntegrante");

  if (inputNombre) {
    inputNombre.addEventListener("input", function () {
      this.value = capitalizar(this.value);
    });
  }

  if (inputApellido) {
    inputApellido.addEventListener("input", function () {
      this.value = capitalizar(this.value);
    });
  }

  if (form) {

    configurarFiltrosPaginacionIntegrantes();

    cargarIntegrantes();

    form.addEventListener("submit", async function (e) {
      e.preventDefault();

      const id = document.getElementById("id_integrante").value.trim();
      const nombreActual = (document.getElementById("nombre").value || "").trim();
      const apellidoActual = (document.getElementById("apellido").value || "").trim();

      const datos = new FormData(form);
      let url = "../php/registrar_integrante.php";

      if (id !== "") {
        url = "../php/actualizar_integrante.php";
        datos.append("id", id);
      }

      try {
        const res = await fetch(url, {
          method: "POST",
          body: datos
        });

        const raw = await res.text();
        const respuesta = interpretarRespuestaServidor(raw, id !== "");

        if (!respuesta.ok) {
          Swal.fire("Error", respuesta.mensaje, "error");
          return;
        }

        form.reset();
        document.getElementById("id_integrante").value = "";

        await cargarIntegrantes();

        if (respuesta.accion === "reactivado") {
          let idIntegrante = respuesta.id_integrante ?? null;

          if (!idIntegrante) {
            idIntegrante = await buscarIdIntegrantePorNombre(nombreActual, apellidoActual);
          }

          await Swal.fire({
            icon: "info",
            title: "Integrante reactivado",
            html: `
              <p>La alumna ya pertenecía al elenco.</p>
              <p>Se reactivó su registro sin empezar desde cero.</p>
            `,
            confirmButtonText: "Ver historial"
          });

          if (idIntegrante) {
            await mostrarHistorialIntegrante(idIntegrante, true);
          } else {
            Swal.fire("Aviso", "Se reactivó correctamente, pero no se pudo ubicar el historial para mostrarlo.", "warning");
          }

          return;
        }

        if (respuesta.accion === "actualizado") {
          Swal.fire({
            icon: "success",
            title: "Integrante actualizado correctamente",
            timer: 1500,
            showConfirmButton: false
          });
          return;
        }

        Swal.fire({
          icon: "success",
          title: "Integrante registrado correctamente",
          timer: 1500,
          showConfirmButton: false
        });

      } catch (error) {
        Swal.fire({
          icon: "error",
          title: "Error de conexión",
          text: error.toString()
        });
      }
    });
  }
});


async function cargarDashboardInicio() {
  try {
    const respuesta = await fetch("php/obtener_dashboard.php?v=" + Date.now());

    const texto = await respuesta.text();
    console.log("Respuesta dashboard:", texto);

    let data;

    try {
      data = JSON.parse(texto);
    } catch (error) {
      console.error("El PHP no devolvió JSON válido:", texto);
      return;
    }

    if (!data.ok) {
      console.error("Error desde obtener_dashboard.php:", data.mensaje);
      return;
    }

    llenarResumenDashboard(data.resumen);
    llenarProximaPresentacion(data.proxima);
    llenarAvisosDashboard(data.avisos);

  } catch (error) {
    console.error("Error al cargar dashboard:", error);
  }
}

function llenarResumenDashboard(resumen) {
  totalIntegrantesGeneralDashboard = resumen.total_integrantes ?? 0;

  setTexto("totalIntegrantes", totalIntegrantesGeneralDashboard);
  setTexto("totalPresentaciones", resumen.total_presentaciones ?? 0);
  setTexto("totalAsignados", resumen.total_asignados ?? 0);
  setTexto("totalEspera", resumen.total_espera ?? 0);
}

function llenarProximaPresentacion(proxima) {
  setTexto("proximaPresentacion", proxima.nombre ?? "--");
  setTexto("fechaPresentacion", formatearFechaDashboard(proxima.fecha));
  setTexto("lugarPresentacion", proxima.lugar ?? "--");
  setTexto("categoriaPresentacion", proxima.categoria ?? "--");
  setTexto("cuposDisponibles", proxima.cupos_disponibles ?? 0);
  setTexto("ocupacionPresentacion", proxima.ocupacion ?? "--");
}

function llenarAvisosDashboard(avisos) {
  const sinAsignar = Number(avisos.sin_asignar ?? 0);
  const limitadas = Number(avisos.presentaciones_limitadas ?? 0);
  const categoria = avisos.categoria_demanda ?? "--";

  setTexto(
    "avisoSinAsignarTitulo",
    `${sinAsignar} integrante${sinAsignar === 1 ? "" : "s"} sin asignar`
  );

  setTexto(
    "avisoSinAsignarTexto",
    sinAsignar > 0
      ? "Requieren revisión para futuras presentaciones."
      : "No hay integrantes pendientes por asignación."
  );

  setTexto(
    "avisoLimitadasTitulo",
    `${limitadas} presentación${limitadas === 1 ? "" : "es"} con capacidad limitada`
  );

  setTexto(
    "avisoLimitadasTexto",
    limitadas > 0
      ? "Podrían requerir reorganización de integrantes."
      : "No hay presentaciones críticas por capacidad."
  );

  setTexto(
    "avisoDemandaTitulo",
    `Categoría con mayor demanda: ${categoria}`
  );

  setTexto(
    "avisoDemandaTexto",
    categoria && categoria !== "Sin datos"
      ? "Se recomienda revisar la disponibilidad del elenco."
      : "Aún no hay suficiente información para calcular demanda."
  );
}

function setTexto(id, valor) {
  const elemento = document.getElementById(id);

  if (!elemento) {
    console.warn("No existe el elemento con id:", id);
    return;
  }

  elemento.textContent = valor;
}

function formatearFechaDashboard(fecha) {
  if (!fecha || fecha === "--") return "--";

  const partes = String(fecha).split("-");

  if (partes.length !== 3) return fecha;

  return `${partes[2]}/${partes[1]}/${partes[0]}`;
}

function capitalizar(texto) {
  return texto.toLowerCase().replace(/\b\w/g, letra => letra.toUpperCase());
}

function interpretarRespuestaServidor(raw, esEdicion = false) {
  const texto = (raw || "").trim();

  try {
    const json = JSON.parse(texto);
    return {
      ok: !!json.ok,
      accion: json.accion || (esEdicion ? "actualizado" : "nuevo"),
      mensaje: json.mensaje || (json.ok ? "Operación exitosa" : "Ocurrió un error"),
      id_integrante: json.id_integrante ?? null
    };
  } catch (_) {
    if (texto === "OK") {
      return {
        ok: true,
        accion: esEdicion ? "actualizado" : "nuevo",
        mensaje: "Operación exitosa",
        id_integrante: null
      };
    }

    if (texto === "REACTIVADO") {
      return {
        ok: true,
        accion: "reactivado",
        mensaje: "Integrante reactivado correctamente",
        id_integrante: null
      };
    }

    return {
      ok: false,
      accion: null,
      mensaje: texto || "Ocurrió un error inesperado",
      id_integrante: null
    };
  }
}

/* CARGAR INTEGRANTES */
async function cargarIntegrantes() {
  const tabla = document.getElementById("tablaIntegrantes");
  if (!tabla) return;

  try {
    const res = await fetch("../php/obtener_integrantes.php");
    const data = await res.json();

    if (!data.ok) {
      tabla.innerHTML = `
        <tr>
          <td colspan="10">${data.mensaje || "Error al cargar integrantes."}</td>
        </tr>
      `;
      return;
    }

    const lista = data.datos;

    if (!Array.isArray(lista) || lista.length === 0) {
      tabla.innerHTML = `
      
        <tr>
          <td colspan="10">No hay integrantes registrados.</td>
        </tr>
      `;
      return;
    }

    let html = "";

    lista.forEach(i => {
      html += `
        <tr>
          <td>${i.nombre ?? ""}</td>
          <td>${i.apellido ?? ""}</td>
          <td>${renderCategoria(i.nombre_categoria ?? "")}</td>
          <td>${i.nombre_nivel ?? ""}</td>
          <td>${i.rapidez_aprendizaje ?? 3}</td>
          <td>${i.anios_antiguedad ?? 0}</td>
          <td>${i.cantidad_presentaciones ?? 0}</td>
          <td>${i.disciplina ?? 3}</td>
          <td>${i.responsabilidad ?? 3}</td>
          <td class="col-acciones">
  <div class="acciones-integrante-mini">
    <button class="btn-mini btn-mini-editar" type="button" onclick="editarIntegrante(
      ${i.id_integrante},
      '${(i.nombre ?? "").replace(/'/g, "\\'")}',
      '${(i.apellido ?? "").replace(/'/g, "\\'")}',
      ${i.id_categoria ?? 0},
      ${i.id_nivel ?? 0},
      ${i.rapidez_aprendizaje ?? 3},
      ${i.anios_antiguedad ?? 0},
      ${i.cantidad_presentaciones ?? 0},
      ${i.disciplina ?? 3},
      ${i.responsabilidad ?? 3}
    )"><span>Editar</span></button>

    <button class="btn-mini btn-mini-retirar" type="button" onclick="retirarIntegrante(${i.id_integrante})">
      <span>Retirar</span>
    </button>

    <button class="btn-mini btn-mini-historial" type="button" onclick="mostrarHistorialIntegrante(${i.id_integrante})">
      <span>Historial</span>
    </button>
  </div>
</td>
        </tr>
      `;
    });

    tabla.innerHTML = html;
    aplicarFiltrosPaginacionIntegrantes();
  } catch (error) {
    console.error("Error al cargar integrantes:", error);
    tabla.innerHTML = `
      <tr>
        <td colspan="10">Error al cargar integrantes.</td>
      </tr>
    `;
  }
}
/* ==========================================
   CONFIGURAR FILTROS Y PAGINACIÓN
========================================== */

function configurarFiltrosPaginacionIntegrantes() {

    const buscar = document.getElementById("buscarIntegrante");

    const categoria = document.getElementById(
        "filtroCategoriaIntegrantes"
    );

    const nivel = document.getElementById(
        "filtroNivelIntegrantes"
    );

    const btnAnterior = document.getElementById(
        "btnAnteriorIntegrantes"
    );

    const btnSiguiente = document.getElementById(
        "btnSiguienteIntegrantes"
    );

    // Buscar por nombre o apellido
    if (buscar) {
        buscar.addEventListener("input", function () {
            paginaListadoIntegrantes = 1;
            aplicarFiltrosPaginacionIntegrantes();
        });
    }

    // Filtrar por categoría
    if (categoria) {
        categoria.addEventListener("change", function () {
            paginaListadoIntegrantes = 1;
            aplicarFiltrosPaginacionIntegrantes();
        });
    }

    // Filtrar por nivel
    if (nivel) {
        nivel.addEventListener("change", function () {
            paginaListadoIntegrantes = 1;
            aplicarFiltrosPaginacionIntegrantes();
        });
    }

    // Página anterior
    if (btnAnterior) {
        btnAnterior.addEventListener("click", function () {

            if (paginaListadoIntegrantes > 1) {
                paginaListadoIntegrantes--;
                aplicarFiltrosPaginacionIntegrantes();
            }

        });
    }

    // Página siguiente
    if (btnSiguiente) {
        btnSiguiente.addEventListener("click", function () {

            if (
                paginaListadoIntegrantes <
                totalPaginasListadoIntegrantes
            ) {
                paginaListadoIntegrantes++;
                aplicarFiltrosPaginacionIntegrantes();
            }

        });
    }
}


/* ==========================================
   APLICAR BÚSQUEDA Y FILTROS
========================================== */

function aplicarFiltrosPaginacionIntegrantes() {

    const tabla = document.getElementById("tablaIntegrantes");

    if (!tabla) return;

    const buscar = normalizarTexto(
        document.getElementById("buscarIntegrante")?.value || ""
    );

    const categoriaSeleccionada = normalizarTexto(
        document.getElementById(
            "filtroCategoriaIntegrantes"
        )?.value || ""
    );

    const nivelSeleccionado = normalizarTexto(
        document.getElementById(
            "filtroNivelIntegrantes"
        )?.value || ""
    );

    // Eliminar mensaje anterior de búsqueda vacía
    const mensajeAnterior = document.getElementById(
        "mensajeSinResultadosIntegrantes"
    );

    if (mensajeAnterior) {
        mensajeAnterior.remove();
    }

    // Obtener solamente las filas de integrantes
    const filas = Array.from(
        tabla.querySelectorAll("tr")
    ).filter(fila => fila.cells.length === 10);

    // Aplicar filtros combinados
    const filasFiltradas = filas.filter(fila => {

        const nombre = normalizarTexto(
            fila.cells[0].textContent
        );

        const apellido = normalizarTexto(
            fila.cells[1].textContent
        );

        const categoria = normalizarTexto(
            fila.cells[2].textContent
        );

        const nivel = normalizarTexto(
            fila.cells[3].textContent
        );

        const nombreCompleto = `${nombre} ${apellido}`;

        const coincideBusqueda =
            buscar === "" ||
            nombreCompleto.includes(buscar);

        const coincideCategoria =
            categoriaSeleccionada === "" ||
            categoria === categoriaSeleccionada ||
            (
                categoriaSeleccionada === "juvenil" &&
                categoria === "juveniles"
            );

        const coincideNivel =
            nivelSeleccionado === "" ||
            nivel === nivelSeleccionado;

        return (
            coincideBusqueda &&
            coincideCategoria &&
            coincideNivel
        );
    });

    const totalFiltrados = filasFiltradas.length;

    totalPaginasListadoIntegrantes = Math.max(
        1,
        Math.ceil(totalFiltrados / integrantesPorPagina)
    );

    // Evitar páginas fuera de rango
    if (
        paginaListadoIntegrantes >
        totalPaginasListadoIntegrantes
    ) {
        paginaListadoIntegrantes =
            totalPaginasListadoIntegrantes;
    }

    // Calcular registros de la página
    const inicio = (
        paginaListadoIntegrantes - 1
    ) * integrantesPorPagina;

    const fin = inicio + integrantesPorPagina;

    const filasPagina = filasFiltradas.slice(inicio, fin);

    // Ocultar filas y mostrar solo las seleccionadas
    filas.forEach(fila => {
        fila.style.display = "none";
    });

    filasPagina.forEach(fila => {
        fila.style.display = "";
    });

    // Mensaje si no hay coincidencias
    if (totalFiltrados === 0 && filas.length > 0) {

        const filaMensaje = document.createElement("tr");

        filaMensaje.id = "mensajeSinResultadosIntegrantes";

        filaMensaje.innerHTML = `
            <td colspan="10" style="text-align:center; padding:25px;">
                No se encontraron integrantes con los filtros seleccionados.
            </td>
        `;

        tabla.appendChild(filaMensaje);
    }

    actualizarControlesPaginacionIntegrantes(
        totalFiltrados,
        inicio
    );
}


/* ==========================================
   ACTUALIZAR CONTROLES DE PAGINACIÓN
========================================== */

function actualizarControlesPaginacionIntegrantes(
    total,
    inicio
) {

    const info = document.getElementById(
        "infoPaginacionIntegrantes"
    );

    const numeroPagina = document.getElementById(
        "paginaActualIntegrantes"
    );

    const btnAnterior = document.getElementById(
        "btnAnteriorIntegrantes"
    );

    const btnSiguiente = document.getElementById(
        "btnSiguienteIntegrantes"
    );

    const desde = total === 0 ? 0 : inicio + 1;

    const hasta = Math.min(
        inicio + integrantesPorPagina,
        total
    );

    if (info) {
        info.textContent =
            `Mostrando ${desde} - ${hasta} de ${total} integrantes`;
    }

    if (numeroPagina) {
        numeroPagina.textContent =
            `Página ${paginaListadoIntegrantes} de ${totalPaginasListadoIntegrantes}`;
    }

    if (btnAnterior) {
        btnAnterior.disabled =
            paginaListadoIntegrantes <= 1;
    }

    if (btnSiguiente) {
        btnSiguiente.disabled =
            paginaListadoIntegrantes >= totalPaginasListadoIntegrantes;
    }
}

/* RETIRAR */
function retirarIntegrante(id) {
  Swal.fire({
    title: "¿Retirar integrante?",
    html: `
      <p>La integrante no será borrada definitivamente.</p>
      <p>Su historial quedará guardado para una futura reincorporación.</p>
    `,
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Sí, retirar",
    cancelButtonText: "Cancelar"
  }).then((result) => {
    if (result.isConfirmed) {
      const datos = new FormData();
      datos.append("id", id);

      fetch("../php/eliminar_integrante.php", {
        method: "POST",
        body: datos
      })
      .then(res => res.text())
      .then(data => {
        if (data.trim() === "OK") {
          Swal.fire({
            icon: "success",
            title: "Integrante retirada correctamente",
            timer: 1500,
            showConfirmButton: false
          });

          cargarIntegrantes();
        } else {
          Swal.fire({
            icon: "error",
            title: "Error",
            text: data
          });
        }
      })
      .catch(error => {
        Swal.fire({
          icon: "error",
          title: "Error de conexión",
          text: error.toString()
        });
      });
    }
  });
}

/* EDITAR */
function editarIntegrante(
  id,
  nombre,
  apellido,
  categoria,
  nivel,
  rapidez,
  antiguedad,
  cantidad_presentaciones,
  disciplina,
  responsabilidad
) {
  const idInput = document.getElementById("id_integrante");
  if (!idInput) return;

  document.getElementById("id_integrante").value = id;
  document.getElementById("nombre").value = nombre;
  document.getElementById("apellido").value = apellido;
  document.getElementById("categoria").value = categoria;
  document.getElementById("nivel").value = nivel;
  document.getElementById("rapidez_aprendizaje").value = rapidez;
  document.getElementById("anios_antiguedad").value = antiguedad;
  document.getElementById("cantidad_presentaciones").value = cantidad_presentaciones;
  document.getElementById("puntaje_disciplina").value = disciplina;
  document.getElementById("puntaje_responsabilidad").value = responsabilidad;

  Swal.fire({
    icon: "info",
    title: "Modo edición",
    text: "Modifica los datos y presiona Registrar para actualizar"
  });
}

/* HISTORIAL */
async function mostrarHistorialIntegrante(idIntegrante, esReingreso = false) {
  try {
    const res = await fetch(`../php/obtener_historial_integrante.php?id_integrante=${idIntegrante}`);
    const data = await res.json();

    if (!data.ok) {
      Swal.fire("Error", data.mensaje || "No se pudo obtener el historial.", "error");
      return;
    }

    Swal.fire({
      html: construirHtmlHistorial(data, esReingreso),
      width: 1050,
      padding: 0,
      showConfirmButton: false,
      showCloseButton: true,
      backdrop: "rgba(3, 10, 20, 0.82)",
      customClass: {
        popup: "swal2-historial-premium",
        htmlContainer: "swal2-historial-html",
        closeButton: "swal2-historial-close"
      },
      didOpen: () => {
        const popup = Swal.getPopup();
        const btnCerrar = popup.querySelector(".historial-btn-cerrar");

        if (btnCerrar) {
          btnCerrar.addEventListener("click", () => Swal.close());
        }
      }
    });

  } catch (error) {
    Swal.fire("Error", error.toString(), "error");
  }
}

function construirHtmlHistorial(data, esReingreso = false) {
  const i = data.integrante || {};
  const historial = Array.isArray(data.historial) ? data.historial : [];

  const nombreCompleto = `${i.nombre ?? ""} ${i.apellido ?? ""}`.trim() || "Sin nombre";
  const iniciales = obtenerIniciales(i.nombre, i.apellido);

  const estado = (i.estado || "--").toString();
  const estadoClase = estado.toLowerCase() === "activo" ? "activo" : "inactivo";

  const totalRetiros = contarMovimientos(historial, "retiro");
  const totalReingresosHistorial = contarMovimientos(historial, "reingreso");
  const totalReingresos = Number(i.cantidad_reingresos ?? 0) || totalReingresosHistorial;

  const ultimoRetiro = obtenerUltimoMovimiento(historial, "retiro");
  const ultimoReingreso = obtenerUltimoMovimiento(historial, "reingreso");

  const totalAsignadas = Number(data.total_asignadas ?? 0);
  const totalPresentaciones = Number(i.cantidad_presentaciones ?? 0);
  const antiguedad = Number(i.anios_antiguedad ?? 0);

  return `
    <div class="historial-modal historial-resumen-ejecutivo">
      <div class="historial-modal-header">
        <div class="historial-titulo-wrap">
          <div class="historial-icono-titulo">
            <i class="fa-regular fa-user"></i>
          </div>
          <div>
            <h2>Historial del integrante</h2>
            <p>Vista resumida para administración</p>
          </div>
        </div>
      </div>

      ${esReingreso ? `
        <div class="historial-alerta-reingreso">
          <i class="fa-solid fa-rotate-left"></i>
          <span>La integrante fue reactivada. Se conserva su historial anterior.</span>
        </div>
      ` : ""}

      <div class="historial-bloque-principal">

        <div class="historial-perfil-ejecutivo">
          <div class="historial-avatar">${escapeHtml(iniciales)}</div>

          <div class="historial-identidad">
            <h3>${escapeHtml(nombreCompleto)}</h3>
            <div class="historial-estado-badge ${estadoClase}">
              <span class="dot"></span>
              ${escapeHtml(capitalizarPrimera(estado))}
            </div>
          </div>

          <div class="historial-dato-actual">
            <span>Categoría actual</span>
            <strong>${escapeHtml(i.nombre_categoria ?? "--")}</strong>
          </div>

          <div class="historial-dato-actual">
            <span>Nivel actual</span>
            <strong>${escapeHtml(i.nombre_nivel ?? "--")}</strong>
          </div>
        </div>

        <div class="historial-kpis">
          <div class="historial-kpi-card retiro">
            <div class="historial-kpi-icon">
              <i class="fa-solid fa-right-from-bracket"></i>
            </div>
            <div>
              <strong>${totalRetiros}</strong>
              <span>Veces retirada</span>
            </div>
          </div>

          <div class="historial-kpi-card reingreso">
            <div class="historial-kpi-icon">
              <i class="fa-solid fa-right-to-bracket"></i>
            </div>
            <div>
              <strong>${totalReingresos}</strong>
              <span>Veces reingresada</span>
            </div>
          </div>

          <div class="historial-kpi-card asignacion">
            <div class="historial-kpi-icon">
              <i class="fa-regular fa-clipboard"></i>
            </div>
            <div>
              <strong>${totalAsignadas}</strong>
              <span>Asignaciones históricas</span>
            </div>
          </div>

          <div class="historial-kpi-card presentacion">
            <div class="historial-kpi-icon">
              <i class="fa-regular fa-star"></i>
            </div>
            <div>
              <strong>${totalPresentaciones}</strong>
              <span>Presentaciones acumuladas</span>
            </div>
          </div>
        </div>

        <div class="historial-seccion">
          <div class="historial-seccion-titulo">
            <i class="fa-regular fa-clock"></i>
            <span>Resumen rápido</span>
          </div>

          <div class="historial-resumen-grid compacto">
            <div class="historial-resumen-item">
              <div class="historial-resumen-icon">
                <i class="fa-regular fa-calendar-plus"></i>
              </div>
              <div>
                <span>Registro inicial</span>
                <strong>${escapeHtml(formatearFecha(i.fecha_registro))}</strong>
              </div>
            </div>

            <div class="historial-resumen-item">
              <div class="historial-resumen-icon historial-resumen-icon--danger">
                <i class="fa-solid fa-right-from-bracket"></i>
              </div>
              <div>
                <span>Último retiro</span>
                <strong>${escapeHtml(ultimoRetiro ? formatearFecha(ultimoRetiro.fecha_movimiento) : "--")}</strong>
              </div>
            </div>

            <div class="historial-resumen-item">
              <div class="historial-resumen-icon">
                <i class="fa-solid fa-right-to-bracket"></i>
              </div>
              <div>
                <span>Último reingreso</span>
                <strong>${escapeHtml(ultimoReingreso ? formatearFecha(ultimoReingreso.fecha_movimiento) : formatearFecha(i.fecha_reingreso))}</strong>
              </div>
            </div>

            <div class="historial-resumen-item">
              <div class="historial-resumen-icon">
                <i class="fa-regular fa-calendar"></i>
              </div>
              <div>
                <span>Antigüedad registrada</span>
                <strong>${antiguedad} año(s)</strong>
              </div>
            </div>
          </div>
        </div>

        <div class="historial-seccion">
          <div class="historial-seccion-titulo">
            <i class="fa-solid fa-chart-simple"></i>
            <span>Lectura rápida del recorrido</span>
          </div>

          <div class="historial-lectura-grid">
            ${construirTarjetaResumenMovimiento("retiro", "Último retiro", ultimoRetiro)}
            ${construirTarjetaResumenMovimiento("reingreso", "Último reingreso", ultimoReingreso)}
          </div>
        </div>

      </div>

      <div class="historial-footer">
        <button type="button" class="historial-btn-cerrar">
          <i class="fa-solid fa-xmark"></i>
          <span>Cerrar</span>
        </button>
      </div>
    </div>
  `;
}

function construirTarjetaResumenMovimiento(tipo, titulo, movimiento) {
  const esRetiro = tipo === "retiro";

  if (!movimiento) {
    return `
      <div class="historial-mov-resumen ${tipo}">
        <div class="historial-mov-head">
          <div class="historial-mov-icon">
            <i class="${esRetiro ? "fa-solid fa-right-from-bracket" : "fa-solid fa-right-to-bracket"}"></i>
          </div>
          <div>
            <h4>${titulo}</h4>
            <span>Sin registro</span>
          </div>
        </div>

        <div class="historial-mov-vacio">
          No se encontró este tipo de movimiento.
        </div>
      </div>
    `;
  }

  return `
    <div class="historial-mov-resumen ${tipo}">
      <div class="historial-mov-head">
        <div class="historial-mov-icon">
          <i class="${esRetiro ? "fa-solid fa-right-from-bracket" : "fa-solid fa-right-to-bracket"}"></i>
        </div>
        <div>
          <h4>${titulo}</h4>
          <span>${escapeHtml(formatearFecha(movimiento.fecha_movimiento))}</span>
        </div>
      </div>

      <div class="historial-mov-datos">
        <div>
          <span>Categoría anterior</span>
          <strong>${escapeHtml(movimiento.categoria_anterior_nombre ?? "--")}</strong>
        </div>

        <div>
          <span>Nivel anterior</span>
          <strong>${escapeHtml(movimiento.nivel_anterior_nombre ?? "--")}</strong>
        </div>

        <div>
          <span>Categoría nueva</span>
          <strong>${escapeHtml(movimiento.categoria_nueva_nombre ?? "--")}</strong>
        </div>

        <div>
          <span>Nivel nuevo</span>
          <strong>${escapeHtml(movimiento.nivel_nuevo_nombre ?? "--")}</strong>
        </div>
      </div>

      <div class="historial-mov-observacion">
        <b>Observación:</b> ${escapeHtml(movimiento.observacion ?? "--")}
      </div>
    </div>
  `;
}

function contarMovimientos(historial, tipoBuscado) {
  return historial.filter(h =>
    normalizarTipo(h.tipo_movimiento) === normalizarTipo(tipoBuscado)
  ).length;
}

function obtenerUltimoMovimiento(historial, tipoBuscado) {
  const filtrados = historial
    .filter(h => normalizarTipo(h.tipo_movimiento) === normalizarTipo(tipoBuscado))
    .sort((a, b) => convertirFechaOrdenable(b.fecha_movimiento) - convertirFechaOrdenable(a.fecha_movimiento));

  return filtrados.length > 0 ? filtrados[0] : null;
}

function normalizarTipo(tipo) {
  return (tipo || "").toString().toLowerCase().trim();
}

function convertirFechaOrdenable(valor) {
  if (!valor || valor === "0000-00-00 00:00:00") return 0;

  const fecha = new Date(String(valor).replace(" ", "T"));
  const time = fecha.getTime();

  return Number.isNaN(time) ? 0 : time;
}

function obtenerIniciales(nombre, apellido) {
  const n = (nombre || "").trim();
  const a = (apellido || "").trim();

  const i1 = n ? n.charAt(0).toUpperCase() : "";
  const i2 = a ? a.charAt(0).toUpperCase() : "";

  return `${i1}${i2}` || "NA";
}

function capitalizarPrimera(texto) {
  const t = (texto || "").toString().trim();
  if (!t) return "--";
  return t.charAt(0).toUpperCase() + t.slice(1);
}

function escapeHtml(valor) {
  return String(valor ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}

function formatearMovimiento(tipo) {
  const mapa = {
    alta: "Alta inicial",
    retiro: "Retiro",
    reingreso: "Reingreso",
    cambio_nivel: "Cambio de nivel",
    cambio_categoria: "Cambio de categoría"
  };
  return mapa[tipo] || tipo || "--";
}

function formatearFecha(valor) {
  if (!valor || valor === "0000-00-00 00:00:00") return "--";

  const fecha = new Date(String(valor).replace(" ", "T"));
  if (isNaN(fecha.getTime())) return String(valor);

  return fecha.toLocaleString("es-BO", {
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
    hour: "2-digit",
    minute: "2-digit"
  });
}

async function buscarIdIntegrantePorNombre(nombre, apellido) {
  try {
    const res = await fetch("../php/obtener_integrantes.php");
    const data = await res.json();

    if (!data.ok || !Array.isArray(data.datos)) return null;

    const encontrado = data.datos.find(i =>
      normalizarTexto(i.nombre) === normalizarTexto(nombre) &&
      normalizarTexto(i.apellido) === normalizarTexto(apellido)
    );

    return encontrado ? encontrado.id_integrante : null;
  } catch (_) {
    return null;
  }
}

function normalizarTexto(texto) {
  return (texto || "")
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .toLowerCase()
    .trim();
}

function normalizarCategoriaColor(texto) {
  return (texto || "")
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .toLowerCase()
    .trim();
}

function obtenerClaseCategoria(categoria) {
  const cat = normalizarCategoriaColor(categoria);

  if (cat === "ninos") return "categoria-ninos";
  if (cat === "aspirantes") return "categoria-aspirantes";
  if (cat === "juveniles" || cat === "juvenil") return "categoria-juveniles";
  if (cat === "elenco oficial") return "categoria-elenco-oficial";

  return "categoria-default";
}

function renderCategoria(categoria) {
  const texto = categoria || "--";
  const clase = obtenerClaseCategoria(texto);

  return `<span class="categoria-badge ${clase}">${escapeCategoria(texto)}</span>`;
}

function escapeCategoria(valor) {
  return String(valor ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}


/* =========================================================
   DASHBOARD - TOTAL DE INTEGRANTES POR CATEGORÍA
========================================================= */

function activarCardCategoriasDashboard() {
  const cardTotal = document.getElementById("cardTotalIntegrantes");

  if (!cardTotal) {
    console.warn("No existe la tarjeta cardTotalIntegrantes");
    return;
  }

  cardTotal.addEventListener("click", function () {
    if (mostrandoCategoriasIntegrantes) {
      mostrarTotalIntegrantesGeneralDashboard();
    } else {
      mostrarIntegrantesPorCategoriaDashboard();
    }
  });
}

function mostrarIntegrantesPorCategoriaDashboard() {
  const contenedor = document.getElementById("contenidoTotalIntegrantes");

  if (!contenedor) {
    console.warn("No existe contenidoTotalIntegrantes");
    return;
  }

  fetch("php/obtener_integrantes_por_categoria.php?v=" + Date.now())
    .then(res => res.json())
    .then(data => {
      if (!data.ok) {
        console.error(data.mensaje || "Error al obtener categorías.");
        return;
      }

      let html = `
        <h3>Total por categoría</h3>
        <div class="lista-categorias-dashboard">
      `;

      data.categorias.forEach(item => {
        html += `
          <div class="categoria-dashboard-item">
            <span>${renderCategoria(item.categoria)}</span>
            <strong>${item.total}</strong>
          </div>
        `;
      });

      html += `
        </div>
        <small class="texto-volver-dashboard">Clic para volver al total general</small>
      `;

      contenedor.innerHTML = html;
      mostrandoCategoriasIntegrantes = true;
    })
    .catch(error => {
      console.error("Error al cargar integrantes por categoría:", error);
    });
}

function mostrarTotalIntegrantesGeneralDashboard() {
  const contenedor = document.getElementById("contenidoTotalIntegrantes");

  if (!contenedor) return;

  contenedor.innerHTML = `
    <h3>Total de Integrantes</h3>
    <p id="totalIntegrantes">${totalIntegrantesGeneralDashboard}</p>
    <span>Registrados en el sistema</span>
  `;

  mostrandoCategoriasIntegrantes = false;
}