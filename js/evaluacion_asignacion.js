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
                completarFiltroCategorias();
                llenarFiltroPresentaciones([]);
                return;
            }

            llenarFiltroCategorias(data);
            completarFiltroCategorias();
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

function completarFiltroCategorias() {
    const select = document.getElementById("filtroCategoria");
    if (!select) return;

    const categorias = [
        "Niños",
        "Juvenil",
        "Aspirantes",
        "Elenco Oficial"
    ];

    const normalizar = texto => String(texto || "")
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .toLowerCase()
        .replace("juveniles", "juvenil")
        .trim();

    categorias.forEach(categoria => {
        const existe = Array.from(select.options).some(
            opcion => normalizar(opcion.value) === normalizar(categoria)
        );

        if (!existe) {
            const opcion = document.createElement("option");
            opcion.value = categoria;
            opcion.textContent = categoria;
            select.appendChild(opcion);
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
                    <div class="suplente-tarjeta">
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
                html: `<div class="suplentes-lista">${html}</div>`,
                width: 800,
                confirmButtonText: "Cerrar",
                customClass: {
                    popup: "swal-suplentes"
                }
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

            if (!Array.isArray(data.integrantes) || data.integrantes.length === 0) {
                Swal.fire({
                    icon: "info",
                    title: "Estado de saturación",
                    text: "No existen integrantes registrados para evaluar."
                });
                return;
            }

            const normalizar = texto => String(texto ?? "")
                .normalize("NFD")
                .replace(/[\u0300-\u036f]/g, "")
                .toLowerCase()
                .trim();

            const categoriaClave = texto => {
                const valor = normalizar(texto);
                return valor === "juveniles" ? "juvenil" : valor;
            };

            const ordenCategoria = {
                "ninos": 1,
                "juvenil": 2,
                "aspirantes": 3,
                "elenco oficial": 4
            };

            const ordenEstado = {
                "saturado": 1,
                "normal": 2,
                "sin_asignaciones": 3
            };

            const integrantes = [...data.integrantes].sort((a, b) => {
                const catA = ordenCategoria[categoriaClave(a.categoria)] ?? 99;
                const catB = ordenCategoria[categoriaClave(b.categoria)] ?? 99;

                if (catA !== catB) return catA - catB;

                const estA = ordenEstado[a.estado_saturacion] ?? 99;
                const estB = ordenEstado[b.estado_saturacion] ?? 99;

                if (estA !== estB) return estA - estB;

                return String(a.nombre_completo || "").localeCompare(
                    String(b.nombre_completo || ""), "es"
                );
            });

            const porPagina = 5;
            let paginaActual = 1;

            Swal.fire({
                icon: "info",
                title: "Estado de saturación",
                width: 900,
                confirmButtonText: "Cerrar",
                customClass: {
                    popup: "swal-saturacion-suave"
                },
                html: `
                    <div class="saturacion-filtros">

                        <div>
                            <label for="satBuscar">Buscar integrante</label>
                            <input
                                type="text"
                                id="satBuscar"
                                placeholder="Nombre o apellido..."
                                autocomplete="off"
                            >
                        </div>

                        <div>
                            <label for="satCategoria">Categoría</label>
                            <select id="satCategoria">
                                <option value="">Todas las categorías</option>
                                <option value="Niños">Niños</option>
                                <option value="Juvenil">Juvenil</option>
                                <option value="Aspirantes">Aspirantes</option>
                                <option value="Elenco Oficial">Elenco Oficial</option>
                            </select>
                        </div>

                        <div>
                            <label for="satPresentaciones">Presentaciones activas</label>
                            <select id="satPresentaciones">
                                <option value="">Todas</option>
                                <option value="0">0 presentaciones</option>
                                <option value="1">1 presentación</option>
                                <option value="2">2 presentaciones</option>
                                <option value="3">3 presentaciones</option>
                                <option value="4">4 presentaciones</option>
                                <option value="5">5 presentaciones</option>
                                <option value="6+">6 o más</option>
                            </select>
                        </div>
                    </div>

                    <div class="saturacion-resultados" id="satResultados"></div>

                    <div class="saturacion-modal-lista" id="satLista"></div>

                    <div class="saturacion-paginacion">
                        <button type="button" id="satAnterior">
                            <i class="fa-solid fa-chevron-left"></i>
                            Anterior
                        </button>

                        <span id="satPagina"></span>

                        <button type="button" id="satSiguiente">
                            Siguiente
                            <i class="fa-solid fa-chevron-right"></i>
                        </button>
                    </div>
                `,

                didOpen: popup => {
                    const buscar = popup.querySelector("#satBuscar");
                    const filtroCategoria = popup.querySelector("#satCategoria");
                    const filtroPresentaciones = popup.querySelector("#satPresentaciones");
                    const lista = popup.querySelector("#satLista");
                    const resultados = popup.querySelector("#satResultados");
                    const textoPagina = popup.querySelector("#satPagina");
                    const anterior = popup.querySelector("#satAnterior");
                    const siguiente = popup.querySelector("#satSiguiente");

                    function obtenerFiltrados() {
                        const texto = normalizar(buscar.value);
                        const categoria = categoriaClave(filtroCategoria.value);
                        const cantidad = filtroPresentaciones.value;

                        return integrantes.filter(item => {
                            const nombre = normalizar(item.nombre_completo);
                            const categoriaItem = categoriaClave(item.categoria);
                            const total = Number(item.total_presentaciones || 0);

                            const cumpleNombre =
                                nombre.includes(texto);

                            const cumpleCategoria =
                                !categoria || categoriaItem === categoria;

                            let cumplePresentaciones = true;

                            if (cantidad === "6+") {
                                cumplePresentaciones = total >= 6;
                            } else if (cantidad !== "") {
                                cumplePresentaciones = total === Number(cantidad);
                            }

                            return cumpleNombre &&
                                   cumpleCategoria &&
                                   cumplePresentaciones;
                        });
                    }

                    function generarTarjeta(item) {
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

                        return `
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
                                        <strong>${Number(item.total_presentaciones || 0)}</strong>
                                    </div>
                                    <div>
                                        <small>Jornadas activas</small>
                                        <strong>${Number(item.total_jornadas || 0)}</strong>
                                    </div>
                                    <div>
                                        <small>Minutos acumulados</small>
                                        <strong>${Number(item.minutos_totales || 0)} min</strong>
                                    </div>
                                </div>

                                <div class="saturacion-motivo">
                                    <small>Motivo</small>
                                    <p>${escapeHtml(String(item.motivo || "--"))}</p>
                                </div>
                            </div>
                        `;
                    }

                    function mostrarPagina() {
                        const filtrados = obtenerFiltrados();
                        const totalPaginas = Math.max(
                            1,
                            Math.ceil(filtrados.length / porPagina)
                        );

                        paginaActual = Math.min(
                            Math.max(paginaActual, 1),
                            totalPaginas
                        );

                        const inicio = (paginaActual - 1) * porPagina;
                        const visibles = filtrados.slice(
                            inicio,
                            inicio + porPagina
                        );

                        resultados.textContent =
                            `Mostrando ${visibles.length} de ${filtrados.length} integrantes`;

                        if (visibles.length === 0) {
                            lista.innerHTML = `
                                <p class="saturacion-sin-resultados">
                                    No se encontraron integrantes con estos filtros.
                                </p>
                            `;
                        } else {
                            let categoriaAnterior = "";

                            lista.innerHTML = visibles.map(item => {
                                const categoria = item.categoria || "Sin categoría";
                                const clave = categoriaClave(categoria);

                                let encabezado = "";

                                if (clave !== categoriaAnterior) {
                                    categoriaAnterior = clave;

                                    encabezado = `
                                        <div class="saturacion-categoria-titulo">
                                            ${renderCategoria(categoria)}
                                        </div>
                                    `;
                                }

                                return encabezado + generarTarjeta(item);
                            }).join("");
                        }

                        textoPagina.textContent =
                            `Página ${paginaActual} de ${totalPaginas}`;

                        anterior.disabled = paginaActual <= 1;
                        siguiente.disabled = paginaActual >= totalPaginas;
                    }

                    buscar.addEventListener("input", () => {
                        paginaActual = 1;
                        mostrarPagina();
                    });

                    filtroCategoria.addEventListener("change", () => {
                        paginaActual = 1;
                        mostrarPagina();
                    });

                    filtroPresentaciones.addEventListener("change", () => {
                        paginaActual = 1;
                        mostrarPagina();
                    });

                    anterior.addEventListener("click", () => {
                        if (paginaActual > 1) {
                            paginaActual--;
                            mostrarPagina();
                        }
                    });

                    siguiente.addEventListener("click", () => {
                        paginaActual++;
                        mostrarPagina();
                    });

                    mostrarPagina();
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