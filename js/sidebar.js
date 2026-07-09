document.addEventListener("DOMContentLoaded", function () {
  
  const body = document.body;
  const sidebar = document.getElementById("sidebar");
  const toggleBtn = document.getElementById("toggleBtn");
  const btnTema = document.getElementById("themeToggle");

  /* =========================
     SIDEBAR
  ========================= */
  if (sidebar && toggleBtn) {
    const links = document.querySelectorAll(".sidebar-menu a");
    const paginaActual = window.location.pathname.split("/").pop();

    links.forEach(link => {
      const linkPagina = link.getAttribute("href").split("/").pop();
      if (paginaActual === linkPagina) {
        link.classList.add("active");
      } else {
        link.classList.remove("active");
      }
    });

    const estadoGuardado = localStorage.getItem("sidebar-estado");

    if (estadoGuardado === "abierto") {
      sidebar.classList.remove("cerrado");
      sidebar.classList.add("abierto");
      body.classList.add("sidebar-abierta");
      toggleBtn.setAttribute("aria-label", "Cerrar menú");
      toggleBtn.setAttribute("title", "Cerrar menú");
    } else {
      sidebar.classList.remove("abierto");
      sidebar.classList.add("cerrado");
      body.classList.remove("sidebar-abierta");
      toggleBtn.setAttribute("aria-label", "Abrir menú");
      toggleBtn.setAttribute("title", "Abrir menú");
    }

    toggleBtn.addEventListener("click", function () {
      const abierto = sidebar.classList.contains("abierto");

      if (abierto) {
        sidebar.classList.remove("abierto");
        sidebar.classList.add("cerrado");
        body.classList.remove("sidebar-abierta");
        toggleBtn.setAttribute("aria-label", "Abrir menú");
        toggleBtn.setAttribute("title", "Abrir menú");
        localStorage.setItem("sidebar-estado", "cerrado");
      } else {
        sidebar.classList.remove("cerrado");
        sidebar.classList.add("abierto");
        body.classList.add("sidebar-abierta");
        toggleBtn.setAttribute("aria-label", "Cerrar menú");
        toggleBtn.setAttribute("title", "Cerrar menú");
        localStorage.setItem("sidebar-estado", "abierto");
      }
    });
  }

  /* =========================
     TEMA
  ========================= */
  const temaGuardado = localStorage.getItem("tema");

if (temaGuardado === "claro") {
  body.classList.add("modo-claro");
} else if (temaGuardado === "oscuro") {
  body.classList.remove("modo-claro");
} else {
  /* tema inicial por defecto */
  body.classList.add("modo-claro");
  localStorage.setItem("tema", "claro");
}

actualizarIconoTema();
actualizarLogoPorTema();

if (btnTema) {
  btnTema.addEventListener("click", function () {
    body.classList.toggle("modo-claro");

    if (body.classList.contains("modo-claro")) {
      localStorage.setItem("tema", "claro");
    } else {
      localStorage.setItem("tema", "oscuro");
    }

    actualizarIconoTema();
    actualizarLogoPorTema();
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

function actualizarLogoPorTema() {
  const logos = document.querySelectorAll(".logo");

  logos.forEach(logo => {
    if (document.body.classList.contains("modo-claro")) {
      logo.src = "/sistema_ballet/img/s3.png";
    } else {
      logo.src = "/sistema_ballet/img/s1.png";
    }
  });
}