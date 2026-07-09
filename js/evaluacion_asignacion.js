document.addEventListener("DOMContentLoaded", function () {
const btnEjecutar = document.getElementById("btnEjecutar");
const btnActualizar = document.getElementById("btnActualizar");
const btnExportarPDF = document.getElementById("btnExportarPDF");
const btnVerSaturacion = document.getElementById("btnVerSaturacion");
const filtroCategoria = document.getElementById("filtroCategoria");
const filtroPresentacion = document.getElementById("filtroPresentacion");
const cerrarModal = document.getElementById("cerrarModal");
const modal = document.getElementById("modalDetalle");

    if (btnEjecutar) {
        btnEjecutar.addEventListener("click", ejecutarAsignacion);
    }

    if (btnActualizar) {
        btnActualizar.addEventListener("click", cargarEvaluacion);
    }

    if (btnExportarPDF) {
    btnExportarPDF.addEventListener("click", exportarEvaluacionPDF);
}
    if (btnVerSaturacion) {
    btnVerSaturacion.addEventListener("click", consultarSaturacionIntegrantes);
}
    if (filtroCategoria) {
    filtroCategoria.addEventListener("change", function () {
        llenarFiltroPresentaciones(window.datosEvaluacion || []);
        aplicarFiltros();
    });
}

    if (filtroPresentacion) {
        filtroPresentacion.addEventListener("change", aplicarFiltros);
    }

    if (cerrarModal) {
        cerrarModal.addEventListener("click", cerrarModalDetalle);
    }

    if (modal) {
        modal.addEventListener("click", function (e) {
            if (e.target === modal) {
                cerrarModalDetalle();
            }
        });
    }

    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") {
            cerrarModalDetalle();
        }
    });

    cargarEvaluacion();
});

function ejecutarAsignacion() {
    fetch("../php/asignacion_inteligente.php")
        .then(response => response.json())
        .then(data => {
            if (!data.ok) {
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: data.mensaje || "No se pudo ejecutar la asignación."
                });
                return;
            }

            if (data.alertas_saturacion && data.alertas_saturacion.length > 0) {
                const lista = data.alertas_saturacion.map(item => `
                    <li style="margin-bottom:8px;">
                        <b>${item.integrante}</b><br>
                        <span>${item.motivo}</span>
                    </li>
                `).join("");

                Swal.fire({
                    icon: "warning",
                    title: "Integrantes saturados detectados",
                    html: `
                        <p>Los siguientes integrantes no fueron considerados por saturación del mismo día:</p>
                        <ul style="text-align:left; max-height:260px; overflow-y:auto; padding-left:18px;">
                            ${lista}
                        </ul>
                        <p style="margin-top:12px;"><b>${data.mensaje}</b></p>
                    `,
                    width: 760
                }).then(() => {
                    cargarEvaluacion();
                });

                return;
            }

            Swal.fire({
                icon: "success",
                title: "Proceso completado",
                text: data.mensaje || "La asignación inteligente se ejecutó correctamente"
            });

            cargarEvaluacion();
        })
        .catch(error => {
            console.error("Error:", error);
            Swal.fire({
                icon: "error",
                title: "Error",
                text: "No se pudo ejecutar la asignación inteligente"
            });
        });
}

function cargarEvaluacion() {
    fetch("../php/obtener_evaluacion_asignacion.php")
        .then(response => response.json())
        .then(data => {
            window.datosEvaluacion = data || [];
            window.datosEvaluacionFiltrados = data || [];

            const tabla = document.getElementById("tablaEvaluacion");
            if (!tabla) return;

            if (!data || data.length === 0) {
                tabla.innerHTML = `
                    <tr>
                        <td colspan="4">No existen evaluaciones registradas.</td>
                    </tr>
                `;
                llenarFiltroCategorias([]);
                llenarFiltroPresentaciones([]);
                return;
            }

            llenarFiltroCategorias(data);
            llenarFiltroPresentaciones(data);
            aplicarFiltros();
        })
        .catch(error => {
            console.error("Error al cargar evaluación:", error);
            const tabla = document.getElementById("tablaEvaluacion");
            if (tabla) {
                tabla.innerHTML = `
                    <tr>
                        <td colspan="4">Error al cargar los datos.</td>
                    </tr>
                `;
            }
        });
}

function renderTabla(data) {
    const tabla = document.getElementById("tablaEvaluacion");
    if (!tabla) return;

    let html = "";

    if (!data || data.length === 0) {
        tabla.innerHTML = `
            <tr>
                <td colspan="4">No hay registros para este filtro.</td>
            </tr>
        `;
        return;
    }

    data.forEach((item, index) => {
        
        html += `
            <tr>
                <td>${item.nombre_presentacion ?? ""}</td>
                <td>${item.nombre_integrante ?? ""}</td>
                <td>${renderCategoria(item.nombre_categoria)}</td>
                <td>
                    <button type="button" class="btn-detalle" onclick="abrirDetalle(${index})">
                        Ver detalle
                    </button>
                    <button type="button" class="btn-detalle" onclick="verSuplentes(${item.id_presentacion})">
                        Ver suplentes
                </button>
                </td>
            </tr>
        `;
    });

    tabla.innerHTML = html;
}

function llenarFiltroCategorias(data) {
    const filtro = document.getElementById("filtroCategoria");
    if (!filtro) return;

    const valorSeleccionado = filtro.value || "Todas";
    filtro.innerHTML = `<option value="Todas">Todas</option>`;

    const categorias = [];

    data.forEach(item => {
        const categoria = (item.nombre_categoria || "").trim();
        if (categoria !== "" && !categorias.includes(categoria)) {
            categorias.push(categoria);
        }
    });

    categorias.sort();

    categorias.forEach(categoria => {
        const option = document.createElement("option");
        option.value = categoria;
        option.textContent = categoria;
        filtro.appendChild(option);
    });

    filtro.value = categorias.includes(valorSeleccionado) ? valorSeleccionado : "Todas";
}

function llenarFiltroPresentaciones(data) {
    const filtroCategoriaEl = document.getElementById("filtroCategoria");
    const filtroPresentacion = document.getElementById("filtroPresentacion");

    if (!filtroCategoriaEl || !filtroPresentacion) return;

    const filtroCategoria = filtroCategoriaEl.value;
    const valorSeleccionado = filtroPresentacion.value || "Todas";

    filtroPresentacion.innerHTML = `<option value="Todas">Todas</option>`;

    let datosFiltrados = data;

    if (filtroCategoria !== "Todas") {
        datosFiltrados = data.filter(item =>
            (item.nombre_categoria || "").trim() === filtroCategoria
        );
    }

    const presentaciones = [];

    datosFiltrados.forEach(item => {
        const presentacion = (item.nombre_presentacion || "").trim();
        if (presentacion !== "" && !presentaciones.includes(presentacion)) {
            presentaciones.push(presentacion);
        }
    });

    presentaciones.sort();

    presentaciones.forEach(presentacion => {
        const option = document.createElement("option");
        option.value = presentacion;
        option.textContent = presentacion;
        filtroPresentacion.appendChild(option);
    });

    filtroPresentacion.value = presentaciones.includes(valorSeleccionado) ? valorSeleccionado : "Todas";
}

function aplicarFiltros() {
    const filtroCategoriaEl = document.getElementById("filtroCategoria");
    const filtroPresentacionEl = document.getElementById("filtroPresentacion");

    const filtroCategoria = filtroCategoriaEl ? filtroCategoriaEl.value.trim() : "Todas";
    const filtroPresentacion = filtroPresentacionEl ? filtroPresentacionEl.value.trim() : "Todas";

    const datos = window.datosEvaluacion || [];

    const filtrados = datos.filter(item => {
        const categoria = (item.nombre_categoria || "").trim();
        const presentacion = (item.nombre_presentacion || "").trim();

        const coincideCategoria =
            filtroCategoria === "Todas" || categoria === filtroCategoria;

        const coincidePresentacion =
            filtroPresentacion === "Todas" || presentacion === filtroPresentacion;

        return coincideCategoria && coincidePresentacion;
    });

    window.datosEvaluacionFiltrados = filtrados;
    renderTabla(filtrados);
}

function abrirDetalle(index) {
    const datos = window.datosEvaluacionFiltrados || [];
    const item = datos[index];
    if (!item) return;

    const contenido = document.getElementById("contenidoDetalleModal");
    const modal = document.getElementById("modalDetalle");

    if (!contenido || !modal) return;

    const claseDecision = item.decision_final === "Asignado"
        ? "estado-asignado"
        : "estado-espera";

    contenido.innerHTML = `
        <div class="detalle-cabecera">
            <div class="detalle-persona">
                <h4>${item.nombre_integrante ?? "--"}</h4>
                <p>${item.nombre_presentacion ?? "--"} · ${item.nombre_categoria ?? "--"}</p>
            </div>
            <div class="detalle-total">
                <span>Puntaje total</span>
                <strong>${parseFloat(item.puntaje_total || 0).toFixed(2)}</strong>
            </div>
        </div>

        <div class="detalle-grid">
            <div class="detalle-item">
                <span class="label">Nivel</span>
                <div class="valor">${item.nombre_nivel ?? "--"}</div>
            </div>

            <div class="detalle-item">
                <span class="label">Puntaje nivel</span>
                <div class="valor">${parseFloat(item.puntaje_nivel || 0).toFixed(2)}</div>
            </div>

            <div class="detalle-item">
                <span class="label">Puntaje rapidez</span>
                <div class="valor">${parseFloat(item.puntaje_rapidez || 0).toFixed(2)}</div>
            </div>

            <div class="detalle-item">
                <span class="label">Puntaje antigüedad</span>
                <div class="valor">${parseFloat(item.puntaje_antiguedad || 0).toFixed(2)}</div>
            </div>

            <div class="detalle-item">
                <span class="label">Puntaje equidad</span>
                <div class="valor">${parseFloat(item.puntaje_equidad || 0).toFixed(2)}</div>
            </div>

            <div class="detalle-item">
                <span class="label">Puntaje experiencia</span>
                <div class="valor">${parseFloat(item.puntaje_experiencia || 0).toFixed(2)}</div>
            </div>

            <div class="detalle-item">
                <span class="label">Decisión final</span>
                <div class="valor ${claseDecision}">${item.decision_final ?? "--"}</div>
            </div>

            <div class="detalle-item">
                <span class="label">Fecha</span>
                <div class="valor">${item.fecha_evaluacion ?? "--"}</div>
            </div>

            <div class="detalle-item full">
    <span class="label">Motivo</span>
    <div class="valor">
        <strong>Estado:</strong> 
        <span class="${item.decision_final === 'Asignado' ? 'estado-asignado' : 'estado-espera'}">
            ${item.decision_final ?? "--"}
        </span>
        <br><br>
        ${formatearMotivo(item.motivo)}
    </div>
</div>
        </div>
    `;

    modal.classList.add("activo");
}


function cerrarModalDetalle() {
    const modal = document.getElementById("modalDetalle");
    if (modal) {
        modal.classList.remove("activo");
    }
}

function escapeHtml(texto) {
    return texto
        .replaceAll("&", "&amp;")
        .replaceAll('"', "&quot;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;");
}
function formatearMotivo(texto) {
    if (!texto) return "--";

    let html = texto
        .replace(/\n/g, "<br>")
        .replace(/- /g, "• ");

    return `<div style="line-height:1.6">${html}</div>`;
}

function limpiarTextoParaPDF(texto) {
    if (!texto) return "--";

    return String(texto)
        .replace(/\n/g, " | ")
        .replace(/\r/g, " ")
        .replace(/\s+/g, " ")
        .trim();
}

function ordenarCategoriasPDF(categorias) {
    const orden = ["Niños", "Juvenil", "Aspirantes", "Elenco Oficial"];

    return categorias.sort((a, b) => {
        const ia = orden.indexOf(a);
        const ib = orden.indexOf(b);

        if (ia === -1 && ib === -1) return a.localeCompare(b);
        if (ia === -1) return 1;
        if (ib === -1) return -1;

        return ia - ib;
    });
}

function exportarEvaluacionPDF() {
    const { jsPDF } = window.jspdf;

    const datos = (window.datosEvaluacionFiltrados && window.datosEvaluacionFiltrados.length > 0)
        ? window.datosEvaluacionFiltrados
        : (window.datosEvaluacion || []);

    if (!datos || datos.length === 0) {
        Swal.fire("Aviso", "No hay datos de evaluación para exportar.", "warning");
        return;
    }

    const doc = new jsPDF("p", "mm", "a4");
    const fechaGeneracion = new Date().toLocaleString("es-BO");

    const grupos = {};
    datos.forEach(item => {
        const categoria = (item.nombre_categoria || "Sin categoría").trim();
        if (!grupos[categoria]) {
            grupos[categoria] = [];
        }
        grupos[categoria].push(item);
    });

    const categorias = ordenarCategoriasPDF(Object.keys(grupos));

    categorias.forEach((categoria, index) => {
        if (index > 0) {
            doc.addPage();
        }

        const items = grupos[categoria] || [];
        const totalAsignados = items.filter(x => (x.decision_final || "").trim() === "Asignado").length;
        const totalEspera = items.filter(x => (x.decision_final || "").trim() !== "Asignado").length;

        doc.setFont("helvetica", "bold");
        doc.setFontSize(18);
        doc.text("Resultados de evaluación", 14, 18);

        doc.setFontSize(15);
        doc.text(`Categoría: ${categoria}`, 14, 28);

        doc.setFont("helvetica", "normal");
        doc.setFontSize(10);
        doc.text(`Generado: ${fechaGeneracion}`, 14, 35);
        doc.text(`Total evaluados: ${items.length}`, 14, 41);
        doc.text(`Asignados: ${totalAsignados}`, 70, 41);
        doc.text(`En espera: ${totalEspera}`, 110, 41);

        const body = items.map((item, i) => ([
            i + 1,
            item.nombre_presentacion || "--",
            item.nombre_integrante || "--",
            item.nombre_nivel || "--",
            item.decision_final || "--",
            parseFloat(item.puntaje_total || 0).toFixed(2),
            limpiarTextoParaPDF(item.motivo)
        ]));

        doc.autoTable({
            startY: 48,
            head: [[
                "#",
                "Presentación",
                "Integrante",
                "Nivel",
                "Decisión",
                "Puntaje",
                /*"Detalle / Motivo"*/
            ]],
            body: body,
            theme: "grid",
            styles: {
                fontSize: 8,
                cellPadding: 2,
                overflow: "linebreak",
                valign: "top"
            },
            headStyles: {
                fillColor: [5, 54, 72],
                textColor: 255,
                fontStyle: "bold"
            },
            columnStyles: {
                0: { cellWidth: 8 },
                1: { cellWidth: 28 },
                2: { cellWidth: 32 },
                3: { cellWidth: 18 },
                4: { cellWidth: 18 },
                5: { cellWidth: 16 },
                6: { cellWidth: 70 }
            },
            margin: { top: 48, left: 10, right: 10, bottom: 15 }
        });
    });

    doc.save("evaluacion_asignacion_por_categoria.pdf");
}

function verSuplentes(idPresentacion) {
    idPresentacion = Number(idPresentacion || 0);

    if (idPresentacion <= 0) {
        Swal.fire("Error", "ID de presentación inválido.", "error");
        return;
    }

    fetch(`../php/obtener_suplentes_presentacion.php?id_presentacion=${idPresentacion}`)
        .then(response => response.json())
        .then(data => {
            if (!data.ok) {
                Swal.fire("Error", data.mensaje || "No se pudieron obtener los suplentes.", "error");
                return;
            }

            if (!data.datos || data.datos.length === 0) {
                Swal.fire("Aviso", "Esta presentación no tiene suplentes registrados.", "info");
                return;
            }

            const nombrePresentacion = data.datos[0].nombre_presentacion || "Presentación";

            const html = data.datos.map(s => `
                <div style="text-align:left; margin-bottom:14px; padding:12px; border:1px solid rgba(0,255,255,0.25); border-radius:10px;">
                    <strong>Suplente ${s.orden_suplente}</strong><br>
                    <b>Nombre:</b> ${s.nombre} ${s.apellido}<br>
                    <b>Categoría:</b> ${s.nombre_categoria}<br>
                    <b>Nivel:</b> ${s.nombre_nivel}<br>
                    <b>Tipo:</b> ${s.tipo_suplencia}<br>
                    <b>Motivo:</b> ${s.motivo_suplencia || "--"}<br>
                    <b>Estado:</b> ${s.estado}
                </div>
            `).join("");

            Swal.fire({
                icon: "info",
                title: `Suplentes de ${nombrePresentacion}`,
                html: `<div style="max-height:420px; overflow-y:auto;">${html}</div>`,
                width: 800,
                confirmButtonText: "Cerrar"
            });
        })
        .catch(error => {
            console.error("Error al cargar suplentes:", error);
            Swal.fire("Error", "No se pudieron cargar los suplentes.", "error");
        });
}

function consultarSaturacionIntegrantes() {
    fetch("../php/consultar_saturacion_integrantes.php")
        .then(response => response.json())
        .then(data => {
            if (!data.ok) {
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: data.mensaje || "No se pudo consultar la saturación."
                });
                return;
            }

            if (!data.integrantes || data.integrantes.length === 0) {
                Swal.fire({
                    icon: "info",
                    title: "Saturación de integrantes",
                    text: "No existen integrantes registrados para evaluar."
                });
                return;
            }

            const ordenCategoria = {
                "Niños": 1,
                "Juvenil": 2,
                "Juveniles": 2,
                "Aspirantes": 3,
                "Elenco Oficial": 4
            };

            const ordenEstado = {
                "saturado": 1,
                "normal": 2,
                "sin_asignaciones": 3
            };

            const integrantesOrdenados = [...data.integrantes].sort((a, b) => {
                const categoriaA = ordenCategoria[a.categoria] || 99;
                const categoriaB = ordenCategoria[b.categoria] || 99;

                if (categoriaA !== categoriaB) return categoriaA - categoriaB;

                const estadoA = ordenEstado[a.estado_saturacion] || 99;
                const estadoB = ordenEstado[b.estado_saturacion] || 99;

                if (estadoA !== estadoB) return estadoA - estadoB;

                return String(a.nombre_completo || "").localeCompare(String(b.nombre_completo || ""));
            });

            let categoriaActual = "";

            const html = integrantesOrdenados.map(item => {
                let claseEstado = "saturacion-info";
                let icono = "fa-circle-info";
                let estado = "Sin asignaciones activas";

                if (item.estado_saturacion === "normal") {
                    claseEstado = "saturacion-disponible";
                    icono = "fa-circle-check";
                    estado = "Disponible";
                }

                if (item.estado_saturacion === "saturado") {
                    claseEstado = "saturacion-alerta";
                    icono = "fa-triangle-exclamation";
                    estado = "Saturado";
                }

                const categoria = item.categoria || "Sin categoría";

                let encabezadoCategoria = "";

                if (categoria !== categoriaActual) {
                    categoriaActual = categoria;

                    encabezadoCategoria = `
                        <div class="saturacion-categoria-titulo">
                            ${renderCategoria(categoria)}
                        </div>
                    `;
                }

                return `
                    ${encabezadoCategoria}

                    <div class="saturacion-card ${claseEstado}">
                        <div class="saturacion-card-header">
                            <div class="saturacion-icono">
                                <i class="fa-solid ${icono}"></i>
                            </div>

                            <div>
                                <h4>${escapeHtml(String(item.nombre_completo || "--"))}</h4>
                                <span>${estado}</span>
                            </div>
                        </div>

                        <div class="saturacion-grid">
                            <div>
                                <small>Categoría</small>
                                <strong>${renderCategoria(categoria)}</strong>
                            </div>

                            <div>
                                <small>Presentaciones activas</small>
                                <strong>${item.total_presentaciones}</strong>
                            </div>

                            <div>
                                <small>Jornadas activas</small>
                                <strong>${item.total_jornadas}</strong>
                            </div>

                            <div>
                                <small>Minutos acumulados</small>
                                <strong>${item.minutos_totales} min</strong>
                            </div>
                        </div>

                        <div class="saturacion-motivo">
                            <small>Motivo</small>
                            <p>${escapeHtml(String(item.motivo || "--"))}</p>
                        </div>
                    </div>
                `;
            }).join("");

            Swal.fire({
                icon: "info",
                title: "Estado de saturación",
                html: `<div class="saturacion-modal-lista">${html}</div>`,
                width: 900,
                confirmButtonText: "Cerrar",
                customClass: {
                    popup: "swal-saturacion-suave"
                }
            });
        })
        .catch(error => {
            console.error("Error al consultar saturación:", error);

            Swal.fire({
                icon: "error",
                title: "Error",
                text: "No se pudo consultar la saturación de integrantes."
            });
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