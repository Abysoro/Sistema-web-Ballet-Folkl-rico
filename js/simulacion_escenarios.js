document.addEventListener("DOMContentLoaded", function () {
  simularEscenario(1);
});

function simularEscenario(escenario) {
  fetch("../php/simular_escenario.php?escenario=" + escenario)
    .then(res => res.json())
    .then(data => {
      mostrarResumen(data);
      mostrarDetalle(data.detalle);
    })
    .catch(error => {
      console.error("Error al simular escenario:", error);

      document.getElementById("resumenSimulacion").innerHTML = `
        <div style="color:#ff4d6d; font-weight:bold;">
          Error al cargar la simulación.
        </div>
      `;

      document.getElementById("tablaSimulacion").innerHTML = `
        <tr>
          <td colspan="3">No se pudo cargar la información.</td>
        </tr>
      `;
    });
}

function mostrarResumen(data) {
  const contenedor = document.getElementById("resumenSimulacion");

  contenedor.innerHTML = `
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:15px;">
      <div class="card-resumen" style="padding:15px; border:1px solid #00ffff44; border-radius:10px;">
        <strong>Escenario</strong><br>
        ${data.titulo}
      </div>

      <div class="card-resumen" style="padding:15px; border:1px solid #00ffff44; border-radius:10px;">
        <strong>Integrantes evaluados</strong><br>
        ${data.total_integrantes}
      </div>

      <div class="card-resumen" style="padding:15px; border:1px solid #00ffff44; border-radius:10px;">
        <strong>Asignados</strong><br>
        ${data.asignados}
      </div>

      <div class="card-resumen" style="padding:15px; border:1px solid #00ffff44; border-radius:10px;">
        <strong>En espera</strong><br>
        ${data.espera}
      </div>

      <div class="card-resumen" style="padding:15px; border:1px solid #00ffff44; border-radius:10px;">
        <strong>Tiempo promedio de espera</strong><br>
        ${data.tiempo_promedio}
      </div>
    </div>

    <div style="margin-top:18px; padding:15px; border:1px solid #00ffff44; border-radius:10px;">
      <strong>Análisis del flujo:</strong><br>
      ${data.analisis}
    </div>
  `;
}

function mostrarDetalle(detalle) {
  const tabla = document.getElementById("tablaSimulacion");
  tabla.innerHTML = "";

  if (!detalle || detalle.length === 0) {
    tabla.innerHTML = `
      <tr>
        <td colspan="3">No existen datos para este escenario.</td>
      </tr>
    `;
    return;
  }

  detalle.forEach(item => {
    tabla.innerHTML += `
      <tr>
        <td>${item.nombre ?? "--"}</td>
        <td>${item.estado ?? "--"}</td>
        <td>${item.motivo ?? "--"}</td>
      </tr>
    `;
  });
}