document.addEventListener("DOMContentLoaded", function () {
  cargarPresentaciones();
  cargarIntegrantes();
});

let idPresentacionSeleccionada = null;

function traducirCategoria(cat) {
  switch (String(cat)) {
    case "1": return "Niños";
    case "2": return "Juvenil";
    case "3": return "Aspirantes";
    case "4": return "Elenco Oficial";
    default: return cat || "Sin categoría";
  }
}

function cargarPresentaciones() {
  fetch("../php/obtener_presentaciones.php")
    .then(res => res.json())
    .then(data => {
      let tabla = document.getElementById("tablaPresentaciones");
      tabla.innerHTML = "";

      data.forEach(p => {
        tabla.innerHTML += `
          <tr>
            <td>${p.fecha}</td>
            <td>${p.nombre_presentacion}</td>
            <td>${p.nombre_categoria ? p.nombre_categoria : traducirCategoria(p.categoria)}</td>
            <td><span class="capacidad-badge">${p.capacidad}</span></td>
            <td>
              <button type="button" onclick="seleccionarPresentacion(${p.id_presentacion}, this)">
                Elegir
              </button>
            </td>
          </tr>
        `;
      });
    });
}

function seleccionarPresentacion(id, boton) {
  idPresentacionSeleccionada = id;

  document.querySelectorAll("#tablaPresentaciones tr").forEach(fila => {
    fila.classList.remove("fila-seleccionada");
  });

  boton.closest("tr").classList.add("fila-seleccionada");

  Swal.fire({
    icon: "success",
    title: "Danza seleccionada",
    timer: 1000,
    showConfirmButton: false
  });
}

function cargarIntegrantes() {
  fetch("../php/obtener_integrantes.php")
    .then(res => res.json())
    .then(data => {
      let tabla = document.getElementById("tablaIntegrantesDisponibles");
      tabla.innerHTML = "";

      data.forEach(i => {
        tabla.innerHTML += `
          <tr>
            <td>${i.nombre}</td>
            <td>${i.apellido}</td>
            <td>${i.nombre_categoria}</td>
            <td>${i.nombre_nivel}</td>
            <td>
              <button type="button" onclick="asignar(${i.id_integrante})">
                <i class="fa-solid fa-plus"></i> Asignar
              </button>
            </td>
          </tr>
        `;
      });
    });
}

function asignar(id_integrante) {
  if (!idPresentacionSeleccionada) {
    Swal.fire({
      icon: "warning",
      title: "Selecciona una danza primero"
    });
    return;
  }

  let datos = new FormData();
  datos.append("id_integrante", id_integrante);
  datos.append("id_presentacion", idPresentacionSeleccionada);

  fetch("../php/asignar_integrante.php", {
    method: "POST",
    body: datos
  })
    .then(res => res.text())
    .then(data => {
      if (data.trim() == "OK") {
        Swal.fire({
          icon: "success",
          title: "Integrante asignado"
        });
      } else {
        Swal.fire("Error", data, "error");
      }
    });
}