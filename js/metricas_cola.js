document.addEventListener("DOMContentLoaded", function () {
  recalcularMetricas();

  const btnReporteTeoriaColas = document.getElementById("btnReporteTeoriaColas");

if (btnReporteTeoriaColas) {
  btnReporteTeoriaColas.addEventListener("click", exportarReporteTeoriaColasPDF);
}
});
function formatearNumero(valor) {
  if (valor === null || valor === undefined || valor === "") {
    return "0.00";
  }
  return Number(valor).toFixed(2);
}

function recalcularMetricas() {
  fetch("../php/calcular_metricas.php")
    .then(res => res.text())
    .then(data => {
      if (data.trim() === "OK") {
        cargarMetricasCola();
      } else {
        console.error("Error al recalcular métricas:", data);
        document.getElementById("tablaMetricasCola").innerHTML = `
          <tr>
            <td colspan="6">Error al recalcular métricas.</td>
          </tr>
        `;
      }
    })
    .catch(error => {
      console.error("Error al recalcular métricas:", error);
      document.getElementById("tablaMetricasCola").innerHTML = `
        <tr>
          <td colspan="6">Error al recalcular métricas.</td>
        </tr>
      `;
    });
}

function cargarMetricasCola() {
  fetch("../php/obtener_metricas_cola.php")
    .then(res => res.json())
    .then(data => {
      let tabla = document.getElementById("tablaMetricasCola");
      tabla.innerHTML = "";

      if (data.length === 0) {
        tabla.innerHTML = `
          <tr>
            <td colspan="6">No hay métricas calculadas todavía.</td>
          </tr>
        `;
        return;
      }

      data.forEach(m => {
        tabla.innerHTML += `
          <tr>
            <td>${m.nombre_presentacion ?? "--"}</td>
            <td>${formatearNumero(m.tasa_llegada)}</td>
            <td>${formatearNumero(m.tasa_servicio)}</td>
            <td>${m.longitud_cola ?? 0}</td>
            <td>${formatearTiempoEspera(m.tiempo_hasta_completar_segundos)}</td>
            <td>${formatearNumero(m.utilizacion_sistema)}</td>
          </tr>
        `;
      });
    })
    .catch(error => {
      console.error("Error al cargar métricas:", error);
      document.getElementById("tablaMetricasCola").innerHTML = `
        <tr>
          <td colspan="6">Error al cargar métricas.</td>
        </tr>
      `;
    });
}

function exportarReporteTeoriaColasPDF() {
  fetch("../php/reporte_teoria_colas.php?v=" + Date.now())
    .then(res => res.json())
    .then(data => {
      if (!data.ok) {
        Swal.fire("Error", data.mensaje || "No se pudo generar el reporte.", "error");
        return;
      }

      if (!data.datos || data.datos.length === 0) {
        Swal.fire("Aviso", "No existen datos de teoría de colas para exportar.", "info");
        return;
      }

      const { jsPDF } = window.jspdf;
      const doc = new jsPDF("l", "mm", "a4");

      const fecha = new Date().toLocaleString("es-BO");

      doc.setFont("helvetica", "bold");
      doc.setFontSize(16);
      doc.text("Reporte de teoría de colas", 14, 16);

      doc.setFont("helvetica", "normal");
      doc.setFontSize(10);
      doc.text("Elenco Artístico Sueño y Danza", 14, 23);
      doc.text("Indicadores del proceso de asignación de integrantes", 14, 29);
      doc.text(`Generado: ${fecha}`, 14, 35);

      doc.setFont("helvetica", "bold");
      doc.setFontSize(9);
      doc.text(
        "Este reporte muestra los indicadores principales del modelo de teoría de colas aplicado al proceso de asignación.",
        14,
        42
      );

      const body = data.datos.map((item, index) => [
        index + 1,
        item.presentacion,
        item.tasa_llegada,
        item.tasa_servicio,
        item.longitud_cola,
        item.tiempo_hasta_completar,
        item.utilizacion_sistema
      ]);

      doc.autoTable({
        startY: 49,
        head: [[
          "#",
          "Presentación",
          "Tasa de llegada",
          "Tasa de servicio",
          "Longitud de cola",
          "Tiempo espera promedio",
          "Utilización del sistema"
        ]],
        body: body,
        theme: "grid",
        styles: {
          fontSize: 8,
          cellPadding: 2.2,
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
          0: { cellWidth: 10 },
          1: { cellWidth: 48 },
          2: { cellWidth: 35 },
          3: { cellWidth: 35 },
          4: { cellWidth: 35 },
          5: { cellWidth: 45 },
          6: { cellWidth: 45 }
        },
        margin: { left: 12, right: 12 }
      });

      doc.save("reporte_teoria_colas.pdf");
    })
    .catch(error => {
      console.error("Error al generar reporte teoría de colas:", error);
      Swal.fire("Error", "No se pudo generar el PDF de teoría de colas.", "error");
    });
}



function formatearTiempoEspera(segundos) {
    if (segundos === null || segundos === undefined || segundos === "") {
        return "Sin dato";
    }

    segundos = Number(segundos);

    if (!Number.isFinite(segundos)) {
        return "Sin dato";
    }

    segundos = Math.max(0, Math.floor(segundos));

    if (segundos === 0) {
        return "0 seg";
    }


    const dias = Math.floor(segundos / 86400);
    const horas = Math.floor((segundos % 86400) / 3600);
    const minutos = Math.floor((segundos % 3600) / 60);
    const seg = segundos % 60;

    if (dias > 0) {
        let texto = `${dias} ${dias === 1 ? "día" : "días"}`;

        if (horas > 0) texto += ` ${horas} h`;
        if (minutos > 0) texto += ` ${minutos} min`;

        return texto;
    }

    if (horas > 0) {
        let texto = `${horas} h`;

        if (minutos > 0) texto += ` ${minutos} min`;
        if (seg > 0) texto += ` ${seg} seg`;

        return texto;
    }

    if (minutos > 0) {
        return `${minutos} min${seg > 0 ? ` ${seg} seg` : ""}`;
    }

    return `${seg} seg`;
}
