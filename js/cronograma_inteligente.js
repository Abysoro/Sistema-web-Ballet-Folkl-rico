document.addEventListener("DOMContentLoaded", () => {
  cargarJornadas();

  const btnCargar = document.getElementById("btnCargar");
  const btnOptimizar = document.getElementById("btnOptimizar");
  const btnGuardar = document.getElementById("btnGuardar");

  if (btnCargar) {
    btnCargar.addEventListener("click", cargarJornada);
  }

  if (btnOptimizar) {
    btnOptimizar.addEventListener("click", optimizarCronograma);
  }

  if (btnGuardar) {
    btnGuardar.addEventListener("click", guardarCronograma);
  } else {
    console.warn("No se encontró el botón con id='btnGuardar'. Revisa el HTML.");
  }
});

function cargarJornadas() {
  fetch("../phpcrono/obtener_jornadas.php")
    .then(res => res.json())
    .then(data => {
      const select = document.getElementById("filtroJornada");
      const inputFecha = document.getElementById("fechaJornadaSeleccionada");

      select.innerHTML = `<option value="">Seleccione una jornada</option>`;

      if (inputFecha) {
        inputFecha.value = "";
      }

      if (!data.ok || !data.jornadas || data.jornadas.length === 0) {
        select.innerHTML = `<option value="">No hay jornadas registradas</option>`;
        return;
      }

      data.jornadas.forEach(jornada => {
        const option = document.createElement("option");

        option.value = jornada.id_jornada;
        option.dataset.fecha = jornada.fecha || "";
        option.dataset.lugar = jornada.lugar || "";
        option.dataset.horaInicio = jornada.hora_inicio || "";
        option.dataset.horaFin = jornada.hora_fin || "";

        option.textContent = `${jornada.nombre_jornada} - ${jornada.fecha} - ${jornada.lugar}`;

        select.appendChild(option);
      });

      select.addEventListener("change", mostrarFechaJornadaSeleccionada);
    })
    .catch(error => {
      console.error("Error al cargar jornadas:", error);
      alert("Error al cargar las jornadas");
    });
}

function mostrarFechaJornadaSeleccionada() {
  const select = document.getElementById("filtroJornada");
  const inputFecha = document.getElementById("fechaJornadaSeleccionada");

  if (!select || !inputFecha) return;

  const opcionSeleccionada = select.options[select.selectedIndex];

  if (!opcionSeleccionada || !opcionSeleccionada.value) {
    inputFecha.value = "";
    return;
  }

  const fecha = opcionSeleccionada.dataset.fecha || "";
  const lugar = opcionSeleccionada.dataset.lugar || "";
  const horaInicio = opcionSeleccionada.dataset.horaInicio || "";
  const horaFin = opcionSeleccionada.dataset.horaFin || "";

  let texto = formatearFechaJornada(fecha);

  if (horaInicio && horaFin) {
    texto += ` | ${horaInicio} - ${horaFin}`;
  }

  if (lugar) {
    texto += ` | ${lugar}`;
  }

  inputFecha.value = texto;
}

function formatearFechaJornada(fecha) {
  if (!fecha) return "--";

  const partes = String(fecha).split("-");

  if (partes.length !== 3) return fecha;

  return `${partes[2]}/${partes[1]}/${partes[0]}`;
}

function cargarJornada() {
  const idJornada = document.getElementById("filtroJornada").value;

  if (!idJornada) {
    alert("Seleccione una jornada");
    return;
  }

  fetch(`../phpcrono/obtener_presentaciones_jornada.php?id_jornada=${idJornada}`)
    .then(res => res.json())
    .then(data => {
      let html = "";

      if (!Array.isArray(data) || data.length === 0) {
        html = `
          <tr>
            <td colspan="5">No hay presentaciones registradas en esta jornada</td>
          </tr>
        `;
      } else {
        data.forEach(item => {
          html += `
            <tr>
              <td>${item.nombre_presentacion}</td>
              <td>${renderCategoria(item.nombre_categoria)}</td>
              <td>${item.duracion_minutos} min</td>
              <td>${item.capacidad}</td>
              <td>
                <button type="button" onclick="verIntegrantes(${item.id_presentacion})">
                  Ver integrantes
                </button>
              </td>
            </tr>
          `;
        });
      }

      document.getElementById("tablaPresentaciones").innerHTML = html;
    })
    .catch(error => {
      console.error("Error al cargar presentaciones:", error);
      alert("Error al cargar las presentaciones de la jornada");
    });
}

function optimizarCronograma() {
  const idJornada = document.getElementById("filtroJornada").value;

  if (!idJornada) {
    alert("Seleccione una jornada");
    return;
  }

  fetch(`../phpcrono/optimizar_cronograma.php?id_jornada=${idJornada}`)
    .then(res => res.json())
    .then(data => {
      console.log("Respuesta optimizar:", data);

      if (!data.ok) {
        alert(data.mensaje || "Error al optimizar cronograma");
        return;
      }

      let htmlCronograma = "";
      let htmlConflictos = "";

      if (!data.cronograma || data.cronograma.length === 0) {
        htmlCronograma = `
          <tr>
            <td colspan="6">No hay cronograma generado</td>
          </tr>
        `;
      } else {
        data.cronograma.forEach(item => {
          htmlCronograma += `
            <tr>
              <td>${item.orden_salida}</td>
              <td>${item.nombre_presentacion}</td>
              <td>${item.hora_inicio_estimada ?? "-"}</td>
              <td>${item.hora_fin_estimada ?? "-"}</td>
              <td>${item.conflictos_previos}</td>
              <td>${item.observacion ?? ""}</td>
            </tr>
          `;
        });
      }

      if (!data.conflictos || data.conflictos.length === 0) {
        htmlConflictos = `
          <tr>
            <td colspan="6">No se detectaron conflictos</td>
          </tr>
        `;
      } else {
        data.conflictos.forEach(item => {
          htmlConflictos += `
            <tr>
              <td>${item.integrante}</td>
              <td>${item.presentacion_1}</td>
              <td>${item.presentacion_2}</td>
              <td>${item.minutos_requeridos}</td>
              <td>${item.minutos_disponibles}</td>
              <td>${item.estado}</td>
            </tr>
          `;
        });
      }

      document.getElementById("tablaCronograma").innerHTML = htmlCronograma;
      document.getElementById("tablaConflictos").innerHTML = htmlConflictos;

      const btnResumen = document.getElementById("btnVerResumenCronograma");
      const resumenContenido = document.getElementById("contenidoResumenCronograma");

      if (data.resumen && btnResumen && resumenContenido) {
        resumenContenido.innerHTML = `
          <div class="estado-cronograma estado-${data.resumen.clase}">
            <h3>${data.resumen.estado}</h3>
            <p>${data.resumen.detalle}</p>
            <p><strong>Presentaciones ajustadas:</strong> ${data.resumen.presentaciones_ajustadas}</p>
            <p><strong>Minutos extra aplicados:</strong> ${data.resumen.total_minutos_extra}</p>
            <p><strong>Conflictos restantes:</strong> ${data.resumen.conflictos_restantes}</p>
          </div>
        `;
        btnResumen.style.display = "inline-flex";
      }
    })
    .catch(error => {
      console.error("Error al optimizar cronograma:", error);
      alert("Error al optimizar cronograma");
    });
}

function guardarCronograma() {
  const idJornada = document.getElementById("filtroJornada").value;

  if (!idJornada) {
    alert("Seleccione una jornada");
    return;
  }

  fetch(`../phpcrono/guardar_cronograma.php?id_jornada=${idJornada}`)
    .then(res => res.text())
    .then(data => {
      alert(data);
    })
    .catch(error => {
      console.error("Error al guardar cronograma:", error);
      alert("Error al guardar el cronograma");
    });
}

function verIntegrantes(idPresentacion) {
  fetch(`../phpcrono/obtener_integrantes_presentacion.php?id_presentacion=${idPresentacion}`)
    .then(res => res.json())
    .then(data => {
      const modal = document.getElementById("modalIntegrantes");
      const contenido = document.getElementById("contenidoIntegrantes");

      if (!modal || !contenido) {
        console.error("Faltan elementos del modal en el HTML");
        return;
      }

      let html = "";

      if (!data.ok) {
        contenido.innerHTML = `<p>${data.mensaje || "Error al obtener integrantes"}</p>`;
        modal.style.display = "flex";
        document.body.classList.add("modal-abierto");
        return;
      }

      if (!data.integrantes || data.integrantes.length === 0) {
        html = "<p>No hay integrantes registrados en esta presentación.</p>";
      } else {
        html = "<ul>";
        data.integrantes.forEach(item => {
          html += `<li>${item.nombre} ${item.apellido}</li>`;
        });
        html += "</ul>";
      }

      contenido.innerHTML = html;
      modal.style.display = "flex";
      document.body.classList.add("modal-abierto");
    })
    .catch(error => {
      console.error("Error al cargar integrantes:", error);
      alert("No se pudieron cargar los integrantes");
    });
}

function openResumenModal() {
  const modal = document.getElementById("modalResumenCronograma");
  if (!modal) {
    console.error("No existe el modal del resumen");
    return;
  }

  modal.style.display = "flex";
  document.body.classList.add("modal-abierto");
}

function closeModal(idModal) {
  const modal = document.getElementById(idModal);
  if (!modal) {
    console.error(`No existe el modal con id ${idModal}`);
    return;
  }

  modal.style.display = "none";
  document.body.classList.remove("modal-abierto");
}

window.addEventListener("click", function (e) {
  const modalIntegrantes = document.getElementById("modalIntegrantes");
  const modalResumen = document.getElementById("modalResumenCronograma");

  if (e.target === modalIntegrantes) {
    closeModal("modalIntegrantes");
  }

  if (e.target === modalResumen) {
    closeModal("modalResumenCronograma");
  }
});


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