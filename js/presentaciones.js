document.addEventListener("DOMContentLoaded", function () {
  console.log("JS cargado");

  cargarPresentaciones();
  cargarJornadasPresentacion();
  cargarTiposEvento();

  const form = document.getElementById("formPresentacion");
  const btnAsignar = document.getElementById("btnAsignar");
  const btnNuevaJornada = document.getElementById("btnNuevaJornada");
  const inputFecha = document.getElementById("fecha");
  const tipoEvento = document.getElementById("tipo_evento");
  const btnMostrarHistorial = document.getElementById("btnMostrarHistorial");

  if (inputFecha) {
    const hoy = new Date();
    const yyyy = hoy.getFullYear();
    const mm = String(hoy.getMonth() + 1).padStart(2, "0");
    const dd = String(hoy.getDate()).padStart(2, "0");
    inputFecha.min = `${yyyy}-${mm}-${dd}`;
  }

  if (form) {
    form.addEventListener("submit", guardarPresentacion);
    console.log("Formulario conectado");
  } else {
    console.log("No se encontró formPresentacion");
  }

  if (btnAsignar) {
    btnAsignar.addEventListener("click", function () {
      console.log("Botón asignar presionado");
      asignarAutomatico();
    });
  }

  if (btnNuevaJornada) {
    btnNuevaJornada.addEventListener("click", abrirModalNuevaJornada);
  }

  if (tipoEvento) {
    tipoEvento.addEventListener("change", controlarOtroTipoEvento);
  }

  controlarOtroTipoEvento();

 if (btnMostrarHistorial) {
  btnMostrarHistorial.addEventListener("click", abrirHistorialTipoModal);
}

});


function traducirCategoria(cat) {
  switch (String(cat)) {
    case "1": return "Niños";
    case "2": return "Juvenil";
    case "3": return "Aspirantes";
    case "4": return "Elenco Oficial";
    default: return cat;
  }
}

function traducirNivel(nivel) {
  switch (String(nivel)) {
    case "1": return "Básico";
    case "2": return "Intermedio";
    case "3": return "Avanzado";
    default: return nivel;
  }
}
function controlarOtroTipoEvento() {
  const tipoEvento = document.getElementById("tipo_evento");
  const contenedorOtro = document.getElementById("contenedor_otro_tipo");
  const inputOtro = document.getElementById("otro_tipo_evento");

  if (!tipoEvento || !contenedorOtro || !inputOtro) return;

  const textoSeleccionado =
    tipoEvento.options[tipoEvento.selectedIndex]?.text?.trim() || "";

  if (textoSeleccionado === "Otro") {
    contenedorOtro.style.display = "block";
    inputOtro.required = true;
  } else {
    contenedorOtro.style.display = "none";
    inputOtro.required = false;
    inputOtro.value = "";
  }
}
function cargarPresentaciones() {
  fetch("../php/obtener_presentaciones.php")
    .then(res => res.json())
    .then(data => {
      console.log("Presentaciones cargadas:", data);

      const tabla = document.getElementById("tablaPresentaciones");
      tabla.innerHTML = "";

      if (!data.ok) {
        tabla.innerHTML = `
          <tr>
            <td colspan="8">${data.mensaje || "Error al cargar presentaciones."}</td>
          </tr>
        `;
        return;
      }

      const lista = data.datos;

      if (!Array.isArray(lista) || lista.length === 0) {
        tabla.innerHTML = `
          <tr>
            <td colspan="8">No hay presentaciones registradas.</td>
          </tr>
        `;
        return;
      }

      let html = "";

      lista.forEach(p => {
        const nombre = String(p.nombre_presentacion ?? "").replace(/'/g, "\\'");
        const tipoEvento = String(p.id_tipo_evento ?? "").replace(/'/g, "\\'");
        const otroTipo = String(p.otro_tipo_evento ?? "").replace(/'/g, "\\'");
        const categoria = String(p.categoria ?? "").replace(/'/g, "\\'");
        const capacidad = String(p.capacidad ?? "").replace(/'/g, "\\'");
        const nivel_minimo = String(p.nivel_minimo ?? 1).replace(/'/g, "\\'");
        const fecha = String(p.fecha ?? "").replace(/'/g, "\\'");
        const lugar = String(p.lugar ?? "").replace(/'/g, "\\'");
        const jornada = String(p.id_jornada ?? "").replace(/'/g, "\\'");
        const duracion = String(p.duracion_minutos ?? 8).replace(/'/g, "\\'");

        const tipoEventoTexto =
          p.nombre_tipo_evento === "Otro"
            ? `Otro: ${p.otro_tipo_evento || "--"}`
            : (p.nombre_tipo_evento || "--");

        html += `
          <tr>
            <td>${p.nombre_presentacion ?? ""}</td>
            <td>${tipoEventoTexto}</td>
            <td>${renderCategoria(p.nombre_categoria ? p.nombre_categoria : traducirCategoria(p.categoria))}</td>
            <td>${p.capacidad ?? ""}</td>
            <td>${traducirNivel(p.nivel_minimo)}</td>
            <td>${p.fecha ?? ""}</td>
            <td>${p.lugar ?? ""}</td>
            <td>
              <button type="button" class="btn-editar"
                onclick="editarPresentacion('${p.id_presentacion}','${nombre}','${tipoEvento}','${otroTipo}','${categoria}','${capacidad}','${nivel_minimo}','${fecha}','${lugar}','${jornada}','${duracion}')">
                Editar
              </button>

              <button type="button" class="btn-eliminar"
                onclick="eliminarPresentacion('${p.id_presentacion}')">
                Eliminar
              </button>
            </td>
          </tr>
        `;
      });

      tabla.innerHTML = html;
    })
    .catch(error => {
      console.error("Error al cargar presentaciones:", error);
      document.getElementById("tablaPresentaciones").innerHTML = `
        <tr>
          <td colspan="8">Error al cargar presentaciones.</td>
        </tr>
      `;
    });
}

function guardarPresentacion(e) {
  e.preventDefault();

  const fechaSeleccionada = document.getElementById("fecha").value;
  const hoy = new Date();
  const yyyy = hoy.getFullYear();
  const mm = String(hoy.getMonth() + 1).padStart(2, "0");
  const dd = String(hoy.getDate()).padStart(2, "0");
  const fechaHoy = `${yyyy}-${mm}-${dd}`;

  if (!fechaSeleccionada) {
    Swal.fire("Error", "Selecciona una fecha", "error");
    return;
  }

  if (fechaSeleccionada < fechaHoy) {
    Swal.fire("Error", "No se pueden registrar presentaciones con fechas pasadas", "error");
    return;
  }

  const inputCapacidad = document.getElementById("capacidad");
  const inputDuracion = document.getElementById("duracion_minutos");

  [inputCapacidad, inputDuracion].forEach(input => {
  if (!input) return;

  input.addEventListener("input", function () {
    this.value = this.value.replace(/[^0-9]/g, "");

    if (this.value !== "" && parseInt(this.value, 10) < 1) {
      this.value = "1";
    }
  });

  input.addEventListener("blur", function () {
    if (this.value === "" || parseInt(this.value, 10) < 1) {
      this.value = "1";
    }
  });
  });

  const form = document.getElementById("formPresentacion");
  const datos = new FormData(form);
  const id = document.getElementById("id_presentacion").value;

  const jornadaSeleccionada = document.getElementById("jornada").value;
  if (!jornadaSeleccionada) {
    Swal.fire("Error", "Por favor, selecciona una jornada", "error");
    return;
  }
  const capacidad = parseInt(document.getElementById("capacidad").value, 10);
  const duracion = parseInt(document.getElementById("duracion_minutos").value, 10);

  if (isNaN(capacidad) || capacidad < 1) {
  Swal.fire("Error", "La capacidad debe ser como mínimo 1", "error");
  return;
  }

  if (isNaN(duracion) || duracion < 1) {
  Swal.fire("Error", "La duración debe ser como mínimo 1 minuto", "error");
  return;
  } 

  let url = "../php/registrar_presentacion.php";
  if (id !== "") {
    url = "../php/actualizar_presentacion.php";
  }

  fetch(url, {
    method: "POST",
    body: datos
  })
    .then(res => res.text())
    .then(data => {
      if (data.trim() === "OK") {
        Swal.fire({
          icon: "success",
          title: id !== "" ? "Presentación actualizada" : "Presentación guardada"
        });
        form.reset();
        document.getElementById("id_presentacion").value = "";
        controlarOtroTipoEvento();
        cargarPresentaciones();
      } else {
        Swal.fire("Error", data, "error");
      }
    })
    .catch(error => {
      Swal.fire("Error", "No se pudo guardar la presentación", "error");
    });
}

function asignarAutomatico() {
  fetch("../php/asignacion_automatica.php")
    .then(res => res.text())
    .then(data => {
      if (data.trim() === "OK") {
        Swal.fire({
          icon: "success",
          title: "Asignación automática completada",
          text: "Se realizaron nuevas asignaciones."
        });
      } else {
        Swal.fire({
          icon: "info",
          title: "Aviso",
          text: data
        });
      }
    })
    .catch(error => {
      console.error("Error en asignación automática:", error);
      Swal.fire({
        icon: "error",
        title: "Error",
        text: "No se pudo ejecutar la asignación automática"
      });
    });
}


function editarPresentacion(id, nombre, id_tipo_evento, otro_tipo_evento, categoria, capacidad, nivel_minimo, fecha, lugar, jornada, duracion) {
  const campoId = document.getElementById("id_presentacion");
  const campoNombre = document.getElementById("nombre_presentacion");
  const campoTipoEvento = document.getElementById("tipo_evento");
  const campoOtroTipo = document.getElementById("otro_tipo_evento");
  const campoCategoria = document.getElementById("categoria");
  const campoCapacidad = document.getElementById("capacidad");
  const campoNivel = document.getElementById("nivel_minimo");
  const campoFecha = document.getElementById("fecha");
  const campoLugar = document.getElementById("lugar");
  const campoJornada = document.getElementById("jornada");
  const campoDuracion = document.getElementById("duracion_minutos");

  if (campoId) campoId.value = id;
  if (campoNombre) campoNombre.value = nombre;
  if (campoTipoEvento) campoTipoEvento.value = id_tipo_evento;
  if (campoOtroTipo) campoOtroTipo.value = otro_tipo_evento;
  if (campoCategoria) campoCategoria.value = categoria;
  if (campoCapacidad) campoCapacidad.value = capacidad;
  if (campoNivel) campoNivel.value = nivel_minimo;
  if (campoFecha) campoFecha.value = fecha;
  if (campoLugar) campoLugar.value = lugar;
  if (campoJornada) campoJornada.value = jornada;
  if (campoDuracion) campoDuracion.value = duracion;

  controlarOtroTipoEvento();

  Swal.fire({
    icon: "info",
    title: "Modo edición",
    text: "Modifica los datos y presiona Registrar para actualizar"
  });
}

function eliminarPresentacion(id) {
  Swal.fire({
    title: "¿Eliminar presentación?",
    text: "Esta acción no se puede deshacer",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Sí, eliminar",
    cancelButtonText: "Cancelar"
  }).then((result) => {
    if (result.isConfirmed) {
      const datos = new FormData();
      datos.append("id", id);

      fetch("../php/eliminar_presentacion.php", {
        method: "POST",
        body: datos
      })
        .then(res => res.text())
        .then(data => {
          if (data.trim() === "OK") {
            Swal.fire({
              icon: "success",
              title: "Presentación eliminada"
            });
            cargarPresentaciones();
          } else {
            Swal.fire("Error", data, "error");
          }
        })
        .catch(error => {
          console.error("Error al eliminar:", error);
          Swal.fire("Error", "No se pudo eliminar la presentación", "error");
        });
    }
  });
}


function cargarJornadasPresentacion() {
  fetch("../phpcrono/obtener_jornadas.php")
    .then(res => res.json())
    .then(data => {
      const select = document.getElementById("jornada");
      if (!select) return;

      select.innerHTML = "";

      const opcionInicial = document.createElement("option");
      opcionInicial.value = "";
      opcionInicial.textContent = "Seleccione una jornada";
      select.appendChild(opcionInicial);

      if (!data.ok || !data.jornadas || data.jornadas.length === 0) {
        const opcionVacia = document.createElement("option");
        opcionVacia.value = "";
        opcionVacia.textContent = "No hay jornadas registradas";
        select.appendChild(opcionVacia);
        return;
      }

      data.jornadas.forEach(j => {
        const option = document.createElement("option");
        option.value = j.id_jornada;
        option.textContent = `${j.nombre_jornada} - ${j.fecha} - ${j.lugar}`;
        select.appendChild(option);
      });
    })
    .catch(error => {
      console.error("Error al cargar jornadas:", error);
    });
}


function abrirModalNuevaJornada() {
  const fecha = document.getElementById("fecha").value.trim();
  const lugar = document.getElementById("lugar").value.trim();

  if (!fecha || !lugar) {
    Swal.fire({
      icon: "warning",
      title: "Faltan datos",
      text: "Primero selecciona la fecha y escribe el lugar de la presentación."
    });
    return;
  }

  Swal.fire({
    title: "Nueva Jornada",
    html: `
      <input id="swal_nombre_jornada" class="swal2-input" placeholder="Nombre de la jornada" required>
      <input id="swal_hora_inicio" type="time" class="swal2-input" required>
      <input id="swal_hora_fin" type="time" class="swal2-input" required>
    `,
    focusConfirm: false,
    showCancelButton: true,
    confirmButtonText: "Guardar jornada",
    cancelButtonText: "Cancelar",
    preConfirm: () => {
      const nombre_jornada = document.getElementById("swal_nombre_jornada").value.trim();
      const hora_inicio = document.getElementById("swal_hora_inicio").value;
      const hora_fin = document.getElementById("swal_hora_fin").value;

      if (!nombre_jornada || !hora_inicio || !hora_fin) {
        Swal.showValidationMessage("Completa el nombre, la hora de inicio y la hora de fin");
        return false;
      }

      if (hora_fin <= hora_inicio) {
        Swal.showValidationMessage("La hora de fin debe ser mayor a la hora de inicio");
        return false;
      }

      return {
        nombre_jornada,
        fecha,
        lugar,
        hora_inicio,
        hora_fin
      };
    }
  }).then((result) => {
    if (!result.isConfirmed) return;

    const datos = new FormData();
    datos.append("nombre_jornada", result.value.nombre_jornada);
    datos.append("fecha", result.value.fecha);
    datos.append("lugar", result.value.lugar);
    datos.append("hora_inicio", result.value.hora_inicio);
    datos.append("hora_fin", result.value.hora_fin);

    fetch("../phpcrono/registrar_jornada.php", {
      method: "POST",
      body: datos
    })
      .then(res => res.json())
      .then(data => {
        if (data.ok) {
          Swal.fire({
            icon: "success",
            title: "Jornada registrada"
          });

          cargarJornadasPresentacion();

          setTimeout(() => {
            const select = document.getElementById("jornada");
            if (select && data.id_jornada) {
              select.value = String(data.id_jornada);
            }
          }, 300);

        } else {
          Swal.fire("Error", data.mensaje || "No se pudo registrar la jornada", "error");
        }
      })
      .catch(error => {
        console.error("Error al registrar jornada:", error);
        Swal.fire("Error", "No se pudo registrar la jornada", "error");
      });
  });
}

function cargarTiposEvento() {
  fetch("../php/obtener_tipos_evento.php")
    .then(res => res.json())
    .then(data => {
      const select = document.getElementById("tipo_evento");
      if (!select) return;

      select.innerHTML = `<option value="">Seleccione un tipo</option>`;

      if (!data.ok || !data.datos) return;

      data.datos.forEach(item => {
        select.innerHTML += `
          <option value="${item.id_tipo_evento}">
            ${item.nombre_tipo_evento}
          </option>
        `;
      });

      controlarOtroTipoEvento();
    })
    .catch(error => {
      console.error("Error al cargar tipos de evento:", error);
    });
}
function consultarHistorialTipoModal() {
  const select = document.getElementById("swalFiltroTipoHistorial");
  const tabla = document.getElementById("swalTablaHistorialTipo");

  if (!select || !tabla) return;

  const idTipo = Number(select.value || 0);
  const nombreTipo = select.options[select.selectedIndex]?.text?.trim() || "";

  if (idTipo <= 0) {
    tabla.innerHTML = `
      <tr>
        <td colspan="5" style="padding:14px; text-align:center;">
          Selecciona un tipo de evento válido.
        </td>
      </tr>
    `;
    return;
  }

  fetch(`../php/obtener_historial_tipo_evento.php?id_tipo_evento=${idTipo}`)
    .then(res => res.json())
    .then(data => {
      if (!data.ok) {
        tabla.innerHTML = `
          <tr>
            <td colspan="5" style="padding:14px; text-align:center;">
              ${data.mensaje || "No se pudo obtener el historial."}
            </td>
          </tr>
        `;

        window.historialTipoActual = [];
        window.nombreTipoHistorialActual = "";
        return;
      }

      if (!data.datos || data.datos.length === 0) {
        tabla.innerHTML = `
          <tr>
            <td colspan="5" style="padding:14px; text-align:center;">
              No hay registros para este tipo de evento.
            </td>
          </tr>
        `;

        window.historialTipoActual = [];
        window.nombreTipoHistorialActual = nombreTipo;
        return;
      }

      window.historialTipoActual = data.datos;
      window.nombreTipoHistorialActual = nombreTipo;

      let html = "";

      data.datos.forEach(item => {
        const tipoTexto =
          item.nombre_tipo_evento === "Otro"
            ? `Otro: ${item.otro_tipo_evento || "--"}`
            : (item.nombre_tipo_evento || "--");

        html += `
          <tr>
            <td style="padding:10px; border-bottom:1px solid #ddd;">${item.fecha ?? "--"}</td>
            <td style="padding:10px; border-bottom:1px solid #ddd;">${tipoTexto}</td>
            <td style="padding:10px; border-bottom:1px solid #ddd;">${item.nombre_presentacion ?? "--"}</td>
            <td style="padding:10px; border-bottom:1px solid #ddd;">${item.nombre_categoria ?? "--"}</td>
            <td style="padding:10px; border-bottom:1px solid #ddd;">${item.lugar ?? "--"}</td>
          </tr>
        `;
      });

      tabla.innerHTML = html;
    })
    .catch(error => {
      console.error("Error al consultar historial:", error);

      tabla.innerHTML = `
        <tr>
          <td colspan="5" style="padding:14px; text-align:center;">
            Error al cargar historial.
          </td>
        </tr>
      `;

      window.historialTipoActual = [];
      window.nombreTipoHistorialActual = "";
    });
}
function abrirHistorialTipoModal() {
  Swal.fire({
    title: "Historial por tipo de evento",
    width: 1050,
    html: `
      <div style="display:flex; gap:12px; align-items:end; margin-bottom:18px; flex-wrap:wrap;">
        <div style="flex:1; min-width:260px; text-align:left;">
          <label for="swalFiltroTipoHistorial" style="display:block; margin-bottom:6px; font-weight:600;">
            Tipo de evento
          </label>
          <select id="swalFiltroTipoHistorial" class="swal2-input" style="width:100%; margin:0;">
            <option value="">Seleccione un tipo</option>
          </select>
        </div>

        <div style="display:flex; gap:10px; flex-wrap:wrap;">
          <button type="button" id="swalBtnConsultarHistorial" class="swal2-confirm swal2-styled" style="margin:0;">
            Consultar
          </button>

          <button type="button" id="swalBtnExportarHistorialPDF" class="swal2-confirm swal2-styled" style="margin:0; background:#0ea5e9;">
            Exportar PDF
          </button>
        </div>
      </div>

      <div id="swalContenedorHistorial" style="max-height:420px; overflow:auto; text-align:left;">
        <table style="width:100%; border-collapse:collapse; font-size:14px;">
          <thead>
            <tr>
              <th style="padding:10px; border-bottom:1px solid #ccc;">Fecha</th>
              <th style="padding:10px; border-bottom:1px solid #ccc;">Tipo evento</th>
              <th style="padding:10px; border-bottom:1px solid #ccc;">Presentación</th>
              <th style="padding:10px; border-bottom:1px solid #ccc;">Categoría</th>
              <th style="padding:10px; border-bottom:1px solid #ccc;">Lugar</th>
            </tr>
          </thead>
          <tbody id="swalTablaHistorialTipo">
            <tr>
              <td colspan="5" style="padding:14px; text-align:center;">
                Selecciona un tipo de evento y presiona Consultar.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    `,
    showConfirmButton: false,
    didOpen: () => {
      window.historialTipoActual = [];
      window.nombreTipoHistorialActual = "";

      cargarTiposEventoHistorialModal();

      const btnConsultar = document.getElementById("swalBtnConsultarHistorial");
      const btnExportar = document.getElementById("swalBtnExportarHistorialPDF");

      if (btnConsultar) {
        btnConsultar.addEventListener("click", consultarHistorialTipoModal);
      }

      if (btnExportar) {
        btnExportar.addEventListener("click", exportarHistorialTipoPDF);
      }
    }
  });
}
function cargarTiposEventoHistorialModal() {
  fetch("../php/obtener_tipos_evento.php")
    .then(res => res.json())
    .then(data => {
      const select = document.getElementById("swalFiltroTipoHistorial");
      if (!select) return;

      select.innerHTML = `<option value="">Seleccione un tipo</option>`;

      if (!data.ok || !data.datos) return;

      data.datos.forEach(item => {
        select.innerHTML += `
          <option value="${item.id_tipo_evento}">
            ${item.nombre_tipo_evento}
          </option>
        `;
      });
    })
    .catch(error => {
      console.error("Error al cargar tipos para historial:", error);
    });
}

async function exportarHistorialTipoPDF() {
  console.log("PDF NUEVO CON LOGO EJECUTÁNDOSE");

  const datos = window.historialTipoActual || [];
  const nombreTipo = window.nombreTipoHistorialActual || "Tipo de evento";

  if (!datos || datos.length === 0) {
    Swal.fire("Aviso", "Primero consulta un tipo de evento con datos para exportar.", "warning");
    return;
  }

  const { jsPDF } = window.jspdf;
  const doc = new jsPDF("p", "mm", "a4");

  const fechaGeneracion = new Date().toLocaleString("es-BO");

  let logoBase64 = null;

  try {
    logoBase64 = await cargarLogoPDF("/sistema_ballet/img/s1.png");
    console.log("Logo cargado correctamente");
  } catch (error) {
    console.warn("No se pudo cargar el logo:", error);
  }

  dibujarFondoMembretado(doc, logoBase64);

  doc.setFont("helvetica", "bold");
  doc.setFontSize(18);
  doc.setTextColor(5, 54, 72);
  doc.text("Historial por tipo de evento", 105, 22, { align: "center" });

  doc.setFontSize(12);
  doc.setTextColor(0, 0, 0);
  doc.text(`Tipo seleccionado: ${nombreTipo}`, 14, 42);

  doc.setFont("helvetica", "normal");
  doc.setFontSize(10);
  doc.text(`Generado: ${fechaGeneracion}`, 14, 50);
  doc.text(`Total registros: ${datos.length}`, 14, 57);

  const body = datos.map((item, index) => {
    const tipoTexto =
      item.nombre_tipo_evento === "Otro"
        ? `Otro: ${item.otro_tipo_evento || "--"}`
        : (item.nombre_tipo_evento || "--");

    return [
      index + 1,
      item.fecha || "--",
      tipoTexto,
      item.nombre_presentacion || "--",
      item.nombre_categoria || "--",
      item.lugar || "--"
    ];
  });

  doc.autoTable({
    startY: 70,
    head: [[
      "#",
      "Fecha",
      "Tipo evento",
      "Presentación",
      "Categoría",
      "Lugar"
    ]],
    body: body,
    theme: "grid",
    styles: {
      fontSize: 9,
      cellPadding: 3,
      overflow: "linebreak",
      valign: "middle",
      textColor: [35, 35, 35],
      lineColor: [190, 190, 190],
      lineWidth: 0.15
    },
    headStyles: {
      fillColor: [5, 54, 72],
      textColor: [255, 255, 255],
      fontStyle: "bold"
    },
    alternateRowStyles: {
      fillColor: [245, 248, 250]
    },
    margin: {
      top: 70,
      left: 10,
      right: 10,
      bottom: 15
    },
    didDrawPage: function () {
      dibujarPiePDF(doc);
    }
  });

  const nombreArchivo = `historial_${nombreTipo.replace(/\s+/g, "_").toLowerCase()}.pdf`;
  doc.save(nombreArchivo);
}

function cargarLogoPDF(ruta) {
  return new Promise((resolve, reject) => {
    const img = new Image();

    img.onload = function () {
      const canvas = document.createElement("canvas");
      canvas.width = img.naturalWidth;
      canvas.height = img.naturalHeight;

      const ctx = canvas.getContext("2d");
      ctx.drawImage(img, 0, 0);

      const base64 = canvas.toDataURL("image/png");
      resolve(base64);
    };

    img.onerror = function () {
      reject("No se encontró el logo en: " + ruta);
    };

    img.src = ruta + "?v=" + Date.now();
  });
}

function dibujarFondoMembretado(doc, logoBase64) {
  const anchoPagina = doc.internal.pageSize.getWidth();
  const altoPagina = doc.internal.pageSize.getHeight();

  /* franja superior */
  doc.setFillColor(245, 252, 253);
  doc.rect(0, 0, anchoPagina, 34, "F");

  /* línea institucional */
  doc.setDrawColor(0, 180, 204);
  doc.setLineWidth(0.5);
  doc.line(14, 34, anchoPagina - 14, 34);

  if (!logoBase64) return;

  /* logo pequeño superior */
  doc.addImage(logoBase64, "PNG", 14, 7, 28, 20);

  /* marca de agua central */
  try {
    doc.saveGraphicsState();
    doc.setGState(new doc.GState({ opacity: 0.08 }));
    doc.addImage(logoBase64, "PNG", 58, 105, 94, 70);
    doc.restoreGraphicsState();
  } catch (error) {
    console.warn("No se pudo aplicar opacidad, se usará logo suave alternativo.", error);
  }
}

function dibujarPiePDF(doc) {
  const anchoPagina = doc.internal.pageSize.getWidth();
  const altoPagina = doc.internal.pageSize.getHeight();
  const pagina = doc.internal.getNumberOfPages();

  doc.setFont("helvetica", "normal");
  doc.setFontSize(8);
  doc.setTextColor(120, 120, 120);
  doc.text(`Página ${pagina}`, anchoPagina / 2, altoPagina - 8, { align: "center" });
}

async function convertirImagenABase64(url) {
  const respuesta = await fetch(url);

  if (!respuesta.ok) {
    throw new Error("No se pudo cargar la imagen");
  }

  const blob = await respuesta.blob();

  return new Promise((resolve, reject) => {
    const lector = new FileReader();

    lector.onloadend = () => resolve(lector.result);
    lector.onerror = reject;

    lector.readAsDataURL(blob);
  });
}

function agregarMembretePDF(doc, logoBase64, titulo, subtitulo) {
  const anchoPagina = doc.internal.pageSize.getWidth();
  const altoPagina = doc.internal.pageSize.getHeight();

  /* marca de agua centrada */
  if (logoBase64) {
    aplicarOpacidadPDF(doc, 0.07);

    const anchoMarca = 115;
    const altoMarca = 70;
    const xMarca = (anchoPagina - anchoMarca) / 2;
    const yMarca = (altoPagina - altoMarca) / 2;

    doc.addImage(logoBase64, "PNG", xMarca, yMarca, anchoMarca, altoMarca);

    aplicarOpacidadPDF(doc, 1);
  }

  /* logo pequeño superior */
  if (logoBase64) {
    doc.addImage(logoBase64, "PNG", 14, 8, 30, 18);
  }

  /* título institucional */
  doc.setFont("helvetica", "bold");
  doc.setFontSize(16);
  doc.setTextColor(5, 54, 72);
  doc.text(titulo, anchoPagina / 2, 16, { align: "center" });

  doc.setFont("helvetica", "normal");
  doc.setFontSize(9);
  doc.setTextColor(90, 90, 90);
  doc.text(subtitulo, anchoPagina / 2, 23, { align: "center" });

  /* línea de membrete */
  doc.setDrawColor(0, 180, 204);
  doc.setLineWidth(0.4);
  doc.line(14, 30, anchoPagina - 14, 30);
}

function agregarPiePaginaPDF(doc) {
  const anchoPagina = doc.internal.pageSize.getWidth();
  const altoPagina = doc.internal.pageSize.getHeight();

  const paginaActual = doc.internal.getNumberOfPages();

  doc.setFont("helvetica", "normal");
  doc.setFontSize(8);
  doc.setTextColor(120, 120, 120);

  doc.text(
    `Página ${paginaActual}`,
    anchoPagina / 2,
    altoPagina - 8,
    { align: "center" }
  );
}

function aplicarOpacidadPDF(doc, opacidad) {
  try {
    if (doc.setGState && doc.GState) {
      doc.setGState(new doc.GState({ opacity: opacidad }));
    }
  } catch (error) {
    console.warn("El navegador no soportó opacidad en PDF:", error);
  }
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