document.addEventListener("DOMContentLoaded", function () {
  const btnTema = document.getElementById("themeToggle");
  const body = document.body;

  // Tema guardado
  const temaGuardado = localStorage.getItem("tema");

  if (temaGuardado === "claro") {
    body.classList.add("modo-claro");
  } else if (temaGuardado === "oscuro") {
    body.classList.remove("modo-claro");
  }

  actualizarIconoTema();

  if (btnTema) {
    btnTema.addEventListener("click", function () {
      body.classList.toggle("modo-claro");

      if (body.classList.contains("modo-claro")) {
        localStorage.setItem("tema", "claro");
      } else {
        localStorage.setItem("tema", "oscuro");
      }

      actualizarIconoTema();
    });
  }

  function actualizarIconoTema() {
    if (!btnTema) return;

    if (body.classList.contains("modo-claro")) {
      btnTema.innerHTML = `<i class="fa-solid fa-moon"></i>`;
      btnTema.setAttribute("title", "Cambiar a modo oscuro");
      btnTema.setAttribute("aria-label", "Cambiar a modo oscuro");
    } else {
      btnTema.innerHTML = `<i class="fa-solid fa-sun"></i>`;
      btnTema.setAttribute("title", "Cambiar a modo claro");
      btnTema.setAttribute("aria-label", "Cambiar a modo claro");
    }
  }
});