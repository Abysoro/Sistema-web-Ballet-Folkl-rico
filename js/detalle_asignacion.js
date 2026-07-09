document.addEventListener("DOMContentLoaded", function () {
  const filtroCategoria = document.getElementById("filtroCategoria");

  if (filtroCategoria) {
    filtroCategoria.addEventListener("change", cargarDetalleAsignacion);
  }

  const btnExportarHistorial = document.getElementById("btnExportarHistorialAsignacion");

if (btnExportarHistorial) {
  btnExportarHistorial.addEventListener("click", exportarHistorialAsignacionPDF);
}
  cargarDetalleAsignacion();
});


function normalizarTexto(texto) {
  return (texto || "")
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .toLowerCase()
    .trim();
}

function formatearFecha(valor) {
  if (!valor || valor === "0000-00-00 00:00:00") {
    return "--";
  }

  return valor;
}

function formatearTiempo(valor) {
  if (valor === null || valor === undefined || valor === "") {
    return "--";
  }

  return valor;
}

function formatearTipoAsignacion(tipo) {
  if (!tipo || tipo === "normal") return "Normal";

  if (tipo === "mismo_categoria_nivel_inferior") {
    return "Misma categoría / nivel inferior";
  }

  if (tipo === "apoyo_categoria_externa") {
    return "Apoyo de categoría externa";
  }

  return tipo;
}

/* Estado visual */
function claseEstado(estado) {
  const estadoNormalizado = normalizarTexto(estado);

  if (estadoNormalizado === "asignado") {
    return "estado-asignado";
  }

  if (estadoNormalizado === "en espera") {
    return "estado-espera";
  }

  return "";
}

/* Tiempo visual según estado, no según duración */
function claseTiempoPorEstado(estado) {
  const estadoNormalizado = normalizarTexto(estado);

  if (estadoNormalizado === "asignado") {
    return "tiempo-asignado";
  }

  if (estadoNormalizado === "en espera") {
    return "tiempo-espera";
  }

  return "";
}

function cargarDetalleAsignacion() {
  fetch("../php/obtener_detalle_asignacion.php")
    .then(res => res.json())
    .then(data => {
      const tabla = document.getElementById("tablaDetalleAsignacion");
      const filtroCategoria = document.getElementById("filtroCategoria");

      if (!tabla) return;

      const lista = Array.isArray(data) ? data : (data.datos || []);

      const categoriaSeleccionada = filtroCategoria
        ? normalizarTexto(filtroCategoria.value)
        : "todas";

      let datosFiltrados = lista;

      if (categoriaSeleccionada !== "todas") {
        datosFiltrados = lista.filter(item => {
          const categoria = normalizarTexto(item.nombre_categoria);
          return categoria.includes(categoriaSeleccionada);
        });
      }

      if (!Array.isArray(datosFiltrados) || datosFiltrados.length === 0) {
        tabla.innerHTML = `
          <tr>
            <td colspan="11">No hay registros para esta categoría.</td>
          </tr>
        `;
        return;
      }

      let html = "";

      datosFiltrados.forEach(item => {
        const estado = item.estado ?? "--";
        const claseEstadoFila = claseEstado(estado);
        const claseTiempoFila = claseTiempoPorEstado(estado);

        html += `
          <tr>
            <td>${item.integrante ?? "--"}</td>
            <td>${item.apellido ?? "--"}</td>
            <td>${item.nombre_presentacion ?? "--"}</td>
            <td>${renderCategoria(item.nombre_categoria ?? "--")}</td>
            <td>${formatearFecha(item.ingreso_cola)}</td>
            <td>${formatearFecha(item.fecha_asignacion)}</td>
            <td class="${claseTiempoFila}">
              ${formatearTiempo(item.tiempo_espera)}
            </td>
            <td class="${claseEstadoFila}">
              ${estado}
            </td>
            <td>${formatearTipoAsignacion(item.tipo_asignacion)}</td>
            <td>${item.motivo_excepcion ?? "--"}</td>
            <td>${item.veces_no_asignado ?? 0}</td>
          </tr>
        `;
      });

      tabla.innerHTML = html;
    })
    .catch(error => {
      console.error("Error al cargar detalle:", error);

      const tabla = document.getElementById("tablaDetalleAsignacion");

      if (tabla) {
        tabla.innerHTML = `
          <tr>
            <td colspan="11">Error al cargar los datos.</td>
          </tr>
        `;
      }
    });
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


function exportarHistorialAsignacionPDF() {
  Swal.fire({
    title: "Exportar historial de asignaciones",
    text: "Selecciona la categoría que deseas incluir en el reporte.",
    icon: "question",
    input: "select",
    inputOptions: {
      "todas": "Todas las categorías",
      "Niños": "Niños",
      "Juvenil": "Juvenil",
      "Aspirantes": "Aspirantes",
      "Elenco Oficial": "Elenco Oficial"
    },
    inputValue: "todas",
    showCancelButton: true,
    confirmButtonText: "Generar PDF",
    cancelButtonText: "Cancelar",
    inputValidator: (value) => {
      if (!value) {
        return "Debes seleccionar una opción.";
      }
    }
  }).then((result) => {
    if (!result.isConfirmed) return;

    const categoriaSeleccionada = result.value;
    generarPDFHistorialAsignacion(categoriaSeleccionada);
  });
}

function generarPDFHistorialAsignacion(categoriaSeleccionada) {
  const url = "../php/reporte_detalle_asignacion.php?categoria=" + encodeURIComponent(categoriaSeleccionada);

  fetch(url)
    .then(res => res.json())
    .then(data => {
      if (!data.ok) {
        Swal.fire("Error", data.mensaje || "No se pudo generar el reporte.", "error");
        return;
      }

      if (!data.datos || data.datos.length === 0) {
        Swal.fire("Aviso", "No existen registros para la categoría seleccionada.", "info");
        return;
      }

      const { jsPDF } = window.jspdf;
      const doc = new jsPDF("l", "mm", "a4");

      const fecha = new Date().toLocaleString("es-BO");
      const tituloCategoria = categoriaSeleccionada === "todas"
        ? "Todas las categorías"
        : categoriaSeleccionada;

      /* =========================
         ENCABEZADO
      ========================= */
      doc.setFont("helvetica", "bold");
      doc.setFontSize(16);
      doc.text("Reporte histórico de asignaciones", 14, 16);

      doc.setFont("helvetica", "normal");
      doc.setFontSize(10);
      doc.text("Elenco Artístico Sueño y Danza", 14, 23);
      doc.text(`Categoría: ${tituloCategoria}`, 14, 29);
      doc.text(`Generado: ${fecha}`, 14, 35);

      doc.setFont("helvetica", "bold");
      doc.setFontSize(9);
      doc.text(
        "El reporte conserva el historial completo sin eliminar registros de la base de datos.",
        14,
        41
      );

      /* =========================
         TABLA 1: RESUMEN POR INTEGRANTE
      ========================= */
      const resumenBody = (data.resumen || []).map((item, index) => [
        index + 1,
        `${item.integrante} ${item.apellido}`,
        item.categoria,
        item.veces_no_elegido,
        item.tiempo_total_espera,
        item.veces_asignado,
        item.estado_posterior,
        item.asignado_despues_de,
        item.ultima_asignacion
      ]);

      doc.setFont("helvetica", "bold");
      doc.setFontSize(11);
      doc.text("Resumen histórico por integrante", 14, 50);

      doc.autoTable({
        startY: 55,
        head: [[
          "#",
          "Integrante",
          "Categoría",
          "Veces no elegido",
          "Tiempo total de espera",
          "Veces asignado",
          "Estado posterior",
          "Asignado después de",
          "Última asignación"
        ]],
        body: resumenBody,
        theme: "grid",
        styles: {
          fontSize: 7.3,
          cellPadding: 2,
          overflow: "linebreak",
          valign: "middle"
        },
        headStyles: {
          fillColor: [5, 54, 72],
          textColor: 255,
          fontStyle: "bold"
        },
        alternateRowStyles: {
          fillColor: [235, 245, 249]
        },
        columnStyles: {
          0: { cellWidth: 8 },
          1: { cellWidth: 34 },
          2: { cellWidth: 25 },
          3: { cellWidth: 23 },
          4: { cellWidth: 32 },
          5: { cellWidth: 22 },
          6: { cellWidth: 38 },
          7: { cellWidth: 34 },
          8: { cellWidth: 30 }
        },
        margin: { left: 8, right: 8 }
      });

      /* =========================
         SI NO ENTRA, NUEVA PÁGINA
      ========================= */
      let yDetalle = doc.lastAutoTable.finalY + 12;

      if (yDetalle > 165) {
        doc.addPage();
        yDetalle = 18;
      }

      /* =========================
         TABLA 2: DETALLE HISTÓRICO
      ========================= */
      doc.setFont("helvetica", "bold");
      doc.setFontSize(11);
      doc.text("Detalle histórico de asignaciones", 14, yDetalle);

      const detalleBody = data.datos.map((item, index) => [
        index + 1,
        `${item.integrante} ${item.apellido}`,
        item.presentacion,
        item.categoria,
        item.fecha_presentacion,
        item.ingreso_cola,
        item.fecha_asignacion,
        item.tiempo_espera,
        item.estado_asignacion,
        item.veces_no_asignado,
        item.tipo_asignacion
      ]);

      doc.autoTable({
        startY: yDetalle + 5,
        head: [[
          "#",
          "Integrante",
          "Presentación",
          "Categoría",
          "Fecha presentación",
          "Ingreso a cola",
          "Fecha asignación",
          "Tiempo espera",
          "Estado",
          "Veces no asignado",
          "Tipo asignación"
        ]],
        body: detalleBody,
        theme: "grid",
        styles: {
          fontSize: 7.2,
          cellPadding: 2,
          overflow: "linebreak",
          valign: "middle"
        },
        headStyles: {
          fillColor: [5, 54, 72],
          textColor: 255,
          fontStyle: "bold"
        },
        alternateRowStyles: {
          fillColor: [235, 245, 249]
        },
        columnStyles: {
          0: { cellWidth: 8 },
          1: { cellWidth: 30 },
          2: { cellWidth: 28 },
          3: { cellWidth: 24 },
          4: { cellWidth: 24 },
          5: { cellWidth: 31 },
          6: { cellWidth: 31 },
          7: { cellWidth: 25 },
          8: { cellWidth: 22 },
          9: { cellWidth: 22 },
          10: { cellWidth: 30 }
        },
        margin: { left: 8, right: 8 }
      });

      const nombreArchivo = categoriaSeleccionada === "todas"
        ? "reporte_historico_asignaciones_todas.pdf"
        : "reporte_historico_asignaciones_" + categoriaSeleccionada.replaceAll(" ", "_") + ".pdf";

      doc.save(nombreArchivo);
    })
    .catch(error => {
      console.error("Error al exportar historial:", error);
      Swal.fire("Error", "No se pudo generar el PDF.", "error");
    });
}