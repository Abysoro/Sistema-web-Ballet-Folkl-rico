document.addEventListener("DOMContentLoaded", function(){
    cargarAsignados();
});

function cargarAsignados(){

    fetch("../php/obtener_asignaciones.php")
    .then(res => res.json())
    .then(data => {

        console.log(data);

        let tabla = document.getElementById("tablaAsignados");
        tabla.innerHTML = "";

        if (data.length === 0) {
            tabla.innerHTML = `
                <tr>
                    <td colspan="4" style="text-align:center;">
                        No hay integrantes asignados actualmente.
                    </td>
                </tr>
            `;
            return;
        }

        data.forEach(a => {

            tabla.innerHTML += `
                <tr>
                    <td>${escapeCategoria(a.nombre)}</td>
                    <td>${escapeCategoria(a.apellido)}</td>
                    <td>${escapeCategoria(a.nombre_presentacion)}</td>
                    <td>${renderCategoria(a.nombre_categoria)}</td>
                </tr>
            `;

        });

    })
    .catch(error => {
        console.error("Error al cargar asignados:", error);
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