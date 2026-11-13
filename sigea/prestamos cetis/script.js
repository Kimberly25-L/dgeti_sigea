"use strict";

/* CONFIGURACIÓN DE DEMOSTRACIÓN */
const LIMITE = 2;
const CLAVE_INVENTARIO = "cetis120Inventario";
const CLAVE_SOLICITUDES = "cetis120Solicitudes";

const $ = selector => document.querySelector(selector);
const tarjetas = document.querySelectorAll(".tarjeta");

function leerGuardado(clave, predeterminado) {
    try {
        const valor = localStorage.getItem(clave);
        return valor ? JSON.parse(valor) : predeterminado;
    } catch (error) {
        return predeterminado;
    }
}

function guardar(clave, valor) {
    try {
        localStorage.setItem(clave, JSON.stringify(valor));
    } catch (error) {}
}

function mensaje(selector, texto, correcto = true) {
    const elemento = $(selector);
    if (!elemento) return;

    elemento.textContent = texto;
    elemento.style.color = correcto ? "#237448" : "#a52626";
}

function escapar(texto) {
    return String(texto).replace(/[&<>"']/g, caracter => ({
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#39;"
    })[caracter]);
}

/* INVENTARIO COMPARTIDO ENTRE PÁGINAS */
const inventarioInicial = Array.from(tarjetas).map(tarjeta => ({
    nombre: tarjeta.dataset.nombre,
    total: Number(tarjeta.querySelector(".cantidad").textContent),
    disponible: Number(tarjeta.querySelector(".cantidad").textContent)
}));

let inventarioGuardado = leerGuardado(CLAVE_INVENTARIO, null);

let inventario = inventarioInicial.map(inicial => {
    const guardado = Array.isArray(inventarioGuardado)
        ? inventarioGuardado.find(item => item.nombre === inicial.nombre)
        : null;

    return guardado
        ? {
            ...inicial,
            disponible: Math.max(
                0,
                Math.min(inicial.total, Number(guardado.disponible))
            )
        }
        : inicial;
});

let solicitudes = leerGuardado(CLAVE_SOLICITUDES, []);
let seleccionados = [];
let alumno = null;

let siguienteId = solicitudes.reduce(
    (maximo, solicitud) =>
        Math.max(maximo, Number(solicitud.id) || 0),
    0
) + 1;

function guardarInventario() {
    guardar(CLAVE_INVENTARIO, inventario);
}

function guardarSolicitudes() {
    guardar(CLAVE_SOLICITUDES, solicitudes);
}

function buscarInventario(nombre) {
    return inventario.find(item => item.nombre === nombre);
}


/* Sincroniza las cantidades con MySQL para no depender solo del navegador */
async function sincronizarInventarioServidor() {
    try {
        const respuesta = await fetch("./inventario.php", {
            headers: { "Accept": "application/json" }
        });
        const datos = await respuesta.json();
        if (!respuesta.ok || !datos.ok || !Array.isArray(datos.materiales)) return;

        datos.materiales.forEach(remoto => {
            const local = buscarInventario(remoto.nombre);
            if (local) {
                local.total = Number(remoto.total);
                local.disponible = Number(remoto.disponible);
            }
        });
        guardarInventario();
        actualizarCatalogo();
    } catch (error) {
        console.error("No se pudo sincronizar el inventario:", error);
    }
}

/* CATEGORÍAS Y BÚSQUEDA */
function filtrar() {
    const campoBuscador = $("#buscador");
    const campoCategoria = $("#categoria");

    const texto = campoBuscador
        ? campoBuscador.value.trim().toLowerCase()
        : "";

    const categoria = campoCategoria
        ? campoCategoria.value
        : "todos";

    let visibles = 0;

    tarjetas.forEach(tarjeta => {
        const nombre = tarjeta.dataset.nombre.toLowerCase();

        const descripcion = tarjeta
            .querySelector("p:not(.disponible)")
            .textContent.toLowerCase();

        const coincide =
            (nombre.includes(texto) || descripcion.includes(texto)) &&
            (categoria === "todos" ||
                tarjeta.dataset.categoria === categoria);

        tarjeta.hidden = !coincide;

        if (!tarjeta.hidden) visibles++;
    });

    if ($("#sinResultados")) {
        $("#sinResultados").hidden = visibles !== 0;
    }
}

if ($("#btnBuscar")) {
    $("#btnBuscar").addEventListener("click", filtrar);
}

if ($("#buscador")) {
    $("#buscador").addEventListener("input", filtrar);
}

if ($("#categoria")) {
    $("#categoria").addEventListener("change", filtrar);
}

if ($("#btnLimpiarBusqueda")) {
    $("#btnLimpiarBusqueda").addEventListener("click", () => {
        if ($("#buscador")) $("#buscador").value = "";
        if ($("#categoria")) $("#categoria").value = "todos";
        filtrar();
    });
}

/* ACTUALIZAR TARJETAS E INVENTARIO */
function actualizarCatalogo() {
    tarjetas.forEach(tarjeta => {
        const material = buscarInventario(tarjeta.dataset.nombre);

        if (!material) return;

        const boton = tarjeta.querySelector(".btn-prestamo");
        const cantidad = tarjeta.querySelector(".cantidad");

        cantidad.textContent = material.disponible;

        const elegido = seleccionados.includes(material.nombre);

        boton.classList.toggle("seleccionado", elegido);

        if (material.disponible <= 0 && !elegido) {
            boton.disabled = true;
            boton.textContent = "No disponible";
        } else if (elegido) {
            boton.disabled = false;
            boton.textContent = "Quitar selección ✓";
        } else if (seleccionados.length >= LIMITE) {
            boton.disabled = true;
            boton.textContent = "Límite alcanzado";
        } else {
            boton.disabled = false;
            boton.textContent = "Solicitar préstamo";
        }
    });
}

function mostrarSeleccion() {
    const lista = $("#listaSolicitados");

    if (!lista) return;

    lista.replaceChildren();

    if ($("#contador")) {
        $("#contador").textContent =
            `${seleccionados.length} / ${LIMITE}`;
    }

    if (seleccionados.length === 0) {
        const vacio = document.createElement("p");

        vacio.className = "texto-vacio";
        vacio.textContent =
            "Aún no has seleccionado ningún material.";

        lista.appendChild(vacio);
    }

    seleccionados.forEach(nombre => {
        const fila = document.createElement("div");
        fila.className = "fila-material";

        const texto = document.createElement("span");
        texto.textContent = nombre;

        const quitar = document.createElement("button");
        quitar.type = "button";
        quitar.className = "btn-quitar";
        quitar.textContent = "Quitar";

        quitar.addEventListener("click", () => quitarSeleccion(nombre));

        fila.append(texto, quitar);
        lista.appendChild(fila);
    });

    actualizarCatalogo();
}

/* QUITAR UN MATERIAL Y RESTAURAR SU EXISTENCIA */
function quitarSeleccion(nombre) {
    if (!seleccionados.includes(nombre)) return;

    seleccionados = seleccionados.filter(item => item !== nombre);

    const material = buscarInventario(nombre);

    if (material && material.disponible < material.total) {
        material.disponible++;
    }

    guardarInventario();

    mensaje("#mensajeSolicitud", "");
    mostrarSeleccion();
}

/* SELECCIONAR MATERIALES */
document.querySelectorAll(".btn-prestamo").forEach(boton => {
    boton.addEventListener("click", () => {
        if (!cuentaAlumnoAutenticada) {
            mensaje("#mensajeSolicitud", "Inicia sesión desde la parte superior derecha antes de seleccionar materiales.", false);
            const menu = $("#menuAlumno"); if (menu) menu.open = true;
            return;
        }
        const nombre = boton.dataset.material;
        const material = buscarInventario(nombre);

        if (!material) return;

        if (seleccionados.includes(nombre)) {
            quitarSeleccion(nombre);
            return;
        }

        if (material.disponible <= 0) {
            mensaje(
                "#mensajeSolicitud",
                "Este material no está disponible.",
                false
            );
            return;
        }

        if (seleccionados.length >= LIMITE) {
            mensaje(
                "#mensajeSolicitud",
                "Solo puedes seleccionar máximo dos materiales.",
                false
            );
            return;
        }

        seleccionados.push(nombre);
        material.disponible--;

        guardarInventario();

        mensaje("#mensajeSolicitud", "");
        mostrarSeleccion();

       
    });
});

/* CUENTA DEL ALUMNO: inicio de sesión y creación de cuenta */
let cuentaAlumnoAutenticada = null;
let ultimaSolicitudAlumno = null;
let modoEdicionSolicitud = false;
const formularioCuentaAlumno = document.querySelector("#formCuentaAlumno");
async function procesarCuentaAlumno(accion) {
    const nombre = $("#cuentaNombre").value.trim();
    const control = $("#cuentaControl").value.trim();
    const especialidad = $("#cuentaEspecialidad").value;
    const turno = $("#cuentaTurno").value;
    const password = $("#cuentaPassword").value;
    const aviso = $("#mensajeCuentaAlumno");
    if (!/^[0-9]{14}$/.test(control)) { aviso.textContent = "El número de control debe tener exactamente 14 dígitos."; return; }
    if (!nombre || !especialidad || !turno || password.length < 6) { aviso.textContent = "Completa todos los campos. La contraseña debe tener al menos 6 caracteres."; return; }
    if (window.location.protocol === "file:") {
        aviso.textContent = "Para crear cuenta o iniciar sesión, abre el proyecto desde tu servidor local (por ejemplo, http://localhost/...). PHP no funciona al abrir el HTML directamente.";
        aviso.style.color = "#a52626";
        return;
    }
    const botones = [$("#entrarCuentaAlumno"), $("#crearCuentaAlumno")].filter(Boolean);
    botones.forEach(b => { b.disabled = true; });
    const botonAccion = accion === "crear" ? $("#crearCuentaAlumno") : $("#entrarCuentaAlumno");
    const textoOriginal = botonAccion ? botonAccion.textContent : "";
    if (botonAccion) botonAccion.textContent = accion === "crear" ? "Creando cuenta..." : "Iniciando sesión...";
    aviso.style.color = "";
    try {
        const r = await fetch("cuenta_alumno.php", {
            method: "POST",
            credentials: "same-origin",
            headers: {"Content-Type": "application/json", "Accept": "application/json"},
            body: JSON.stringify({accion, nombre, control, especialidad, turno, password})
        });
        const texto = await r.text();
        let d;
        try { d = JSON.parse(texto); }
        catch (_) {
            throw new Error("El servidor no devolvió una respuesta válida. Comprueba que Apache y MySQL estén activos y que la base de datos 'prestamos_cetis120' exista.");
        }
        if (!r.ok || !d.ok) throw new Error(d.mensaje || "No se pudo procesar la cuenta.");
        aviso.textContent = d.mensaje;
        aviso.style.color = "#237448";
        cuentaAlumnoAutenticada = {nombre, control, especialidad, turno, password};
        $("#nombre").value = nombre; $("#control").value = control;
        $("#especialidad").value = especialidad; $("#turno").value = turno;
        $("#passwordSolicitud").value = password;
        $("#nombre").readOnly = true; $("#control").readOnly = true;
        $("#especialidad").disabled = true; $("#turno").disabled = true;
        $("#cerrarSesionAlumno").hidden = false;
        $("#menuAlumno").querySelector("summary").textContent = "Alumno: " + nombre;
        actualizarAccesoAlumno();
    } catch(e) {
        aviso.textContent = e.message || "No se pudo conectar con el servidor. Revisa que Apache, PHP y MySQL estén activos.";
        aviso.style.color = "#a52626";
    } finally {
        botones.forEach(b => { b.disabled = false; });
        if (botonAccion) botonAccion.textContent = textoOriginal;
    }
}
function actualizarAccesoAlumno() {
    const autenticado = Boolean(cuentaAlumnoAutenticada);
    document.querySelectorAll(".btn-prestamo").forEach(b => { b.title = autenticado ? "Seleccionar material" : "Inicia sesión para solicitar materiales"; });
    // El chatbot es de orientación pública y permanece disponible sin iniciar sesión.
}
if (formularioCuentaAlumno) {
    formularioCuentaAlumno.addEventListener("submit", evento => {
        evento.preventDefault();
        const accion = evento.submitter && evento.submitter.value === "crear" ? "crear" : "entrar";
        procesarCuentaAlumno(accion);
    });
}
if ($("#cerrarSesionAlumno")) {
    $("#cerrarSesionAlumno").addEventListener("click", () => {
        cuentaAlumnoAutenticada = null;
        seleccionados = [];
        ultimaSolicitudAlumno = null;
        modoEdicionSolicitud = false;
        $("#formRegistro").reset();
        if (typeof mostrarSeleccion === "function") mostrarSeleccion();
        $("#resultado").innerHTML = "";
        $("#btnEditarRegistro").hidden = true;
        $("#btnCancelarSolicitud").hidden = true;
        $("#btnCancelarEdicion").hidden = true;
        $("#nombre").value = ""; $("#control").value = ""; $("#especialidad").value = ""; $("#turno").value = ""; $("#passwordSolicitud").value = "";
        $("#nombre").readOnly = false; $("#control").readOnly = false; $("#especialidad").disabled = false; $("#turno").disabled = false;
        $("#cerrarSesionAlumno").hidden = true;
        $("#menuAlumno").querySelector("summary").textContent = "Inicio de sesión del alumno";
        $("#mensajeCuentaAlumno").textContent = "Sesión cerrada.";
        actualizarAccesoAlumno();
    });
}

/* REGISTRO Y CONFIRMACIÓN DE SOLICITUD */

const formularioRegistro = document.querySelector("#formRegistro");

if (formularioRegistro) {
    formularioRegistro.addEventListener("submit", async function (evento) {
        evento.preventDefault();

        const nombre = document.querySelector("#nombre").value.trim();
        const control = document.querySelector("#control").value.trim();
        const horario = document.querySelector("#horario").value;
        const especialidad = document.querySelector("#especialidad").value;
        const turno = document.querySelector("#turno").value;
        const passwordSolicitud = $("#passwordSolicitud").value || (cuentaAlumnoAutenticada && cuentaAlumnoAutenticada.password) || "";

        if (!cuentaAlumnoAutenticada) {
            mensaje("#mensajeRegistro", "Primero crea tu cuenta o inicia sesión.", false);
            return;
        }
        if (!/^[0-9]{14}$/.test(control)) {
            mensaje("#mensajeRegistro", "El número de control debe contener exactamente 14 dígitos.", false);
            return;
        }
        if (!nombre || !control || !horario || !especialidad || !turno || !passwordSolicitud) {
            mensaje("#mensajeRegistro", "Inicia sesión nuevamente para continuar.", false);
            return;
        }

        if (!Array.isArray(seleccionados) ||
            seleccionados.length < 1 ||
            seleccionados.length > 2) {
            mensaje("#mensajeRegistro", "Selecciona uno o dos materiales.", false);
            return;
        }

        const materialesEnviar = [...seleccionados];

        const confirmacion =
            (modoEdicionSolicitud ? "¿Guardas los cambios de esta solicitud?\n\n" : "¿Confirmas esta solicitud?\n\n") +
            "Alumno: " + nombre + "\n" +
            "Número de control: " + control + "\n" +
            "Especialidad: " + especialidad + "\n" +
            "Turno: " + turno + "\n" +
            "Tiempo de uso: " + horario + "\n" +
            "Materiales: " + materialesEnviar.join(", ");

        if (!confirm(confirmacion)) {
            return;
        }

        const boton = document.querySelector("#btnRegistro");
        const textoOriginal = boton ? boton.textContent : "";

        if (boton) {
            boton.disabled = true;
            boton.textContent = "Guardando solicitud...";
        }

        try {
            // Verifica la contraseña y actualiza los datos de la cuenta si el alumno corrigió algún campo.
            const actualizarCuenta = await fetch("./cuenta_alumno.php", {
                method: "POST",
                headers: {"Content-Type": "application/json", "Accept": "application/json"},
                body: JSON.stringify({
                    accion: "actualizar",
                    nombre, control, especialidad, turno,
                    controlAnterior: cuentaAlumnoAutenticada.control,
                    password: passwordSolicitud
                })
            });
            const cuentaActualizada = await actualizarCuenta.json();
            if (!actualizarCuenta.ok || !cuentaActualizada.ok) {
                throw new Error(cuentaActualizada.mensaje || "Verifica tus datos y contraseña.");
            }
            cuentaAlumnoAutenticada = {nombre, control, especialidad, turno, password: passwordSolicitud};

            const respuesta = await fetch(modoEdicionSolicitud ? "./editar_solicitud.php" : "./guardar_solicitud.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json"
                },
                body: JSON.stringify({
                    nombre: nombre,
                    control: control,
                    horario: horario,
                    especialidad: especialidad,
                    turno: turno,
                    materiales: materialesEnviar,
                    password: passwordSolicitud,
                    folio: modoEdicionSolicitud && ultimaSolicitudAlumno ? ultimaSolicitudAlumno.folio : null,
                    controlAnterior: modoEdicionSolicitud && ultimaSolicitudAlumno ? ultimaSolicitudAlumno.control : null
                })
            });

            const textoRespuesta = await respuesta.text();
            let datos;

            try {
                datos = JSON.parse(textoRespuesta);
            } catch {
                throw new Error(
                    "El servidor no respondió con JSON. Comprueba que abriste la página desde localhost y que guardar_solicitud.php existe."
                );
            }

            if (!respuesta.ok || !datos.ok) {
                throw new Error(
                    datos.mensaje || "No se pudo guardar la solicitud."
                );
            }

            const folioFinal = datos.folio;
            ultimaSolicitudAlumno = {folio: folioFinal, nombre, control, especialidad, turno, horario, materiales: [...materialesEnviar]};
            modoEdicionSolicitud = false;
            $("#btnRegistro").textContent = "Confirmar solicitud";
            $("#btnEditarRegistro").hidden = false;
            $("#btnCancelarEdicion").hidden = true;
            $("#btnCancelarSolicitud").hidden = false;
            const resultado = document.querySelector("#resultado");

            if (resultado) {
                resultado.innerHTML = "";
                const avisoFolio = document.createElement("article");
                avisoFolio.className = "prestamo-final aviso-folio";
                const recordar = document.createElement("p");
                recordar.textContent = "Recuerda guardar tu folio: lo necesitarás para devolver el material.";
                const folioGrande = document.createElement("h2");
                folioGrande.textContent = "Tu folio es: " + folioFinal;
                const resumen = document.createElement("div");
                resumen.className = "resumen-solicitud";
                resumen.innerHTML = `<p><strong>Alumno:</strong> ${escapar(nombre)}</p>
                    <p><strong>Número de control:</strong> ${escapar(control)}</p>
                    <p><strong>Especialidad:</strong> ${escapar(especialidad)}</p>
                    <p><strong>Turno:</strong> ${escapar(turno)}</p>
                    <p><strong>Tiempo de uso:</strong> ${escapar(horario)}</p>
                    <p><strong>Materiales solicitados:</strong> ${materialesEnviar.map(escapar).join(", ")}</p>
                    <p><strong>Estado:</strong> Pendiente</p>`;
                avisoFolio.append(recordar, folioGrande, resumen);
                resultado.append(avisoFolio);
            }


            seleccionados = [];

            if (typeof guardarInventario === "function") {
                guardarInventario();
            }

            formularioRegistro.reset();
            $("#nombre").value = cuentaAlumnoAutenticada.nombre;
            $("#control").value = cuentaAlumnoAutenticada.control;
            $("#especialidad").value = cuentaAlumnoAutenticada.especialidad;
            $("#turno").value = cuentaAlumnoAutenticada.turno;
            $("#passwordSolicitud").value = passwordSolicitud;

            if (typeof mostrarSeleccion === "function") {
                mostrarSeleccion();
            }

            if (typeof actualizarCatalogo === "function") {
                actualizarCatalogo();
            }

            mensaje(
                "#mensajeRegistro",
                "Tu folio es #" + datos.folio + ". Recuerda guardarlo para devolver el material."
            );

            mensaje("#mensajeSolicitud", "");
            await sincronizarInventarioServidor();

        } catch (error) {
            console.error("Error al registrar:", error);

            mensaje(
                "#mensajeRegistro",
                error.message || "No se pudo conectar con el servidor.",
                false
            );
        } finally {
            if (boton) {
                boton.disabled = false;
                boton.textContent = textoOriginal;
            }
        }
    });
}


/* Editar la solicitud recién registrada sin crear un folio nuevo */
if ($("#btnEditarRegistro")) {
    $("#btnEditarRegistro").addEventListener("click", () => {
        if (!ultimaSolicitudAlumno) return;
        modoEdicionSolicitud = true;
        $("#nombre").readOnly = false;
        $("#control").readOnly = false;
        $("#especialidad").disabled = false;
        $("#turno").disabled = false;
        $("#nombre").value = ultimaSolicitudAlumno.nombre;
        $("#control").value = ultimaSolicitudAlumno.control;
        $("#especialidad").value = ultimaSolicitudAlumno.especialidad;
        $("#turno").value = ultimaSolicitudAlumno.turno;
        $("#horario").value = ultimaSolicitudAlumno.horario;
        seleccionados = [...ultimaSolicitudAlumno.materiales];
        if (typeof mostrarSeleccion === "function") mostrarSeleccion();
        $("#btnRegistro").textContent = "Guardar cambios de la solicitud #" + ultimaSolicitudAlumno.folio;
        $("#btnCancelarEdicion").hidden = false;
        mensaje("#mensajeRegistro", "Edita los datos y confirma para actualizar el mismo folio.");
        $("#formRegistro").scrollIntoView({behavior:"smooth",block:"start"});
    });
    $("#btnCancelarEdicion").addEventListener("click", () => {
        modoEdicionSolicitud = false;
        $("#nombre").readOnly = true; $("#control").readOnly = true;
        $("#especialidad").disabled = true; $("#turno").disabled = true;
        if (cuentaAlumnoAutenticada) {
            $("#nombre").value=cuentaAlumnoAutenticada.nombre; $("#control").value=cuentaAlumnoAutenticada.control;
            $("#especialidad").value=cuentaAlumnoAutenticada.especialidad; $("#turno").value=cuentaAlumnoAutenticada.turno;
        }
        $("#btnRegistro").textContent = "Confirmar solicitud";
        $("#btnCancelarEdicion").hidden = true;
        $("#btnEditarRegistro").hidden = false;
    });
}

/* CANCELAR LA SOLICITUD PENDIENTE DEL ALUMNO */
if ($("#btnCancelarSolicitud")) {
    $("#btnCancelarSolicitud").addEventListener("click", async () => {
        if (!ultimaSolicitudAlumno) return;
        const password = $("#passwordSolicitud").value || (cuentaAlumnoAutenticada && cuentaAlumnoAutenticada.password) || "";
        if (!confirm(`¿Deseas cancelar la solicitud #${ultimaSolicitudAlumno.folio}?`)) return;
        const boton = $("#btnCancelarSolicitud");
        boton.disabled = true;
        try {
            const respuesta = await fetch("./cancelar_solicitud_alumno.php", {
                method: "POST",
                headers: {"Content-Type": "application/json", "Accept": "application/json"},
                body: JSON.stringify({
                    folio: ultimaSolicitudAlumno.folio,
                    control: ultimaSolicitudAlumno.control,
                    password
                })
            });
            const datos = await respuesta.json();
            if (!respuesta.ok || !datos.ok) throw new Error(datos.mensaje || "No se pudo cancelar la solicitud.");
            mensaje("#mensajeRegistro", datos.mensaje);
            $("#resultado").innerHTML = "";
            $("#btnCancelarSolicitud").hidden = true;
            $("#btnEditarRegistro").hidden = true;
            ultimaSolicitudAlumno = null;
            seleccionados = [];
            if (typeof mostrarSeleccion === "function") mostrarSeleccion();
            await sincronizarInventarioServidor();
        } catch (error) {
            mensaje("#mensajeRegistro", error.message || "No se pudo cancelar la solicitud.", false);
        } finally {
            boton.disabled = false;
        }
    });
}

/* DEVOLUCIÓN DE PRÉSTAMOS GUARDADOS EN MYSQL */
const formularioDevolucion = document.querySelector("#formDevolucion");

if (formularioDevolucion) {
    formularioDevolucion.addEventListener("submit", async evento => {
        evento.preventDefault();

        const control = $("#controlDevolucion").value.trim();
        const folio = $("#folioDevolucion").value.trim();
        const boton = $("#btnDevolver");
        const textoOriginal = boton.textContent;

        if (!control || !folio) {
            mensaje("#mensajeDevolucion", "Ingresa tu número de control y el folio.", false);
            return;
        }

        boton.disabled = true;
        boton.textContent = "Procesando devolución...";

        try {
            const respuesta = await fetch("./devolver_material.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json"
                },
                body: JSON.stringify({ control, folio })
            });

            const datos = await respuesta.json();

            if (!respuesta.ok || !datos.ok) {
                throw new Error(datos.mensaje || "No se pudo devolver el material.");
            }

            mensaje("#mensajeDevolucion",
                `Devolución registrada. Folio #${datos.folio}. Materiales: ${datos.materiales}. La disponibilidad se actualizó.`);
            formularioDevolucion.reset();
            await sincronizarInventarioServidor();
        } catch (error) {
            mensaje("#mensajeDevolucion", error.message || "Error de conexión.", false);
        } finally {
            boton.disabled = false;
            boton.textContent = textoOriginal;
        }
    });
}

/* CAPTCHA ARITMÉTICO Y ACCESO DE ADMINISTRADOR */
let respuestaCaptcha = null;

function generarCaptcha() {
    const a = Math.floor(Math.random() * 8) + 2;
    const b = Math.floor(Math.random() * 8) + 1;

    respuestaCaptcha = a + b;

    if ($("#captchaPregunta")) {
        $("#captchaPregunta").textContent =
            `CAPTCHA: ¿Cuánto es ${a} + ${b}?`;
    }

    if ($("#captchaRespuesta")) {
        $("#captchaRespuesta").value = "";
    }
}

if ($("#nuevoCaptcha")) {
    $("#nuevoCaptcha").addEventListener("click", generarCaptcha);
}

if ($("#formAdmin")) {
    generarCaptcha();

    $("#formAdmin").addEventListener("submit", evento => {
        evento.preventDefault();

        const usuario = $("#nombreAdmin").value.trim();
        const contrasena = $("#numeroAdmin").value;
        const valorCaptcha = $("#captchaRespuesta").value.trim();
        const captcha = Number(valorCaptcha);

        if (!usuario || !contrasena || valorCaptcha === "") {
            mensaje(
                "#mensajeAdmin",
                "Completa usuario, contraseña y CAPTCHA.",
                false
            );
            return;
        }

        if (captcha !== respuestaCaptcha) {
            mensaje(
                "#mensajeAdmin",
                "El resultado del CAPTCHA es incorrecto.",
                false
            );

            generarCaptcha();
            return;
        }

        const botonEntrar = evento.submitter || document.querySelector('#formAdmin button[type="submit"]');
        if (botonEntrar) botonEntrar.disabled = true;
        fetch("./iniciar_sesion_admin.php", {
            method: "POST",
            headers: { "Content-Type": "application/json", "Accept": "application/json" },
            body: JSON.stringify({ usuario, contrasena })
        }).then(async respuesta => {
            const datos = await respuesta.json();
            if (!respuesta.ok || !datos.ok) throw new Error(datos.mensaje || "No se pudo iniciar sesión.");
            window.location.href = "panel-admin.php";
        }).catch(error => {
            mensaje("#mensajeAdmin", error.message || "No se pudo conectar con el servidor.", false);
            generarCaptcha();
        }).finally(() => { if (botonEntrar) botonEntrar.disabled = false; });
    });
}

/* CONOCIMIENTOS AGREGADOS DESDE EL PANEL DE ADMINISTRADOR */
let conocimientosIA = [];
fetch("./conocimientos_ia.php", { headers: { "Accept": "application/json" } })
    .then(respuesta => respuesta.ok ? respuesta.json() : [])
    .then(datos => { if (Array.isArray(datos)) conocimientosIA = datos; })
    .catch(() => { conocimientosIA = []; });

function normalizarTextoIA(texto) {
    return String(texto).toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
}

/* CHATBOT */
function agregarMensajeChat(texto, tipo) {
    const burbuja = document.createElement("div");

    burbuja.className = `mensaje-chat ${tipo}`;
    burbuja.textContent = texto;

    $("#chatbotBody").appendChild(burbuja);
    $("#chatbotBody").scrollTop = $("#chatbotBody").scrollHeight;
}

function responder(pregunta) {
    const texto = pregunta.toLowerCase();
    const textoNormalizado = normalizarTextoIA(pregunta);

    if (
        textoNormalizado.includes("como me registro") ||
        textoNormalizado.includes("como registrarme") ||
        textoNormalizado.includes("crear cuenta") ||
        textoNormalizado.includes("hacer una cuenta") ||
        textoNormalizado.includes("registrarme") ||
        textoNormalizado.includes("iniciar sesion") ||
        textoNormalizado.includes("entrar a mi cuenta")
    ) {
        return "Para registrarte, abre el menú de alumno en la parte superior derecha. Elige «Crear cuenta» y completa tu nombre, número de control, especialidad, turno y contraseña. Si ya tienes una cuenta, elige «Iniciar sesión» e ingresa tus datos. Puedes preguntarme aquí si tienes dudas; no necesitas iniciar sesión para usar este chatbot.";
    }

    if (
        textoNormalizado.includes("necesito registrarme") ||
        textoNormalizado.includes("no tengo cuenta") ||
        textoNormalizado.includes("primera vez") ||
        textoNormalizado.includes("como empiezo")
    ) {
        return "¡Te ayudo! No necesitas una cuenta para hablar conmigo. Para solicitar materiales sí debes registrarte o iniciar sesión: abre el menú de alumno en la parte superior derecha y selecciona «Crear cuenta». Completa tu nombre, número de control, especialidad, turno y contraseña.";
    }

    for (const conocimiento of conocimientosIA) {
        const palabras = Array.isArray(conocimiento.palabras_clave) ? conocimiento.palabras_clave : [];
        const coincide = palabras.some(palabra => {
            const clave = normalizarTextoIA(palabra).trim();
            return clave.length > 0 && textoNormalizado.includes(clave);
        });
        if (coincide && conocimiento.respuesta) return conocimiento.respuesta;
    }

    if (texto.includes("hola") || texto.includes("buenas")) {
        return "¡Hola! Soy el asistente del CETis 120. ¿Qué necesitas saber?";
    }

    if (
        texto.includes("solicitar") ||
        texto.includes("pedir") ||
        texto.includes("préstamo")
    ) {
        return "Selecciona hasta dos materiales en el catálogo, completa tu registro y pulsa «Confirmar solicitud».";
    }

    if (texto.includes("material") || texto.includes("catálogo")) {
        return "En el catálogo encontrarás computadoras, cargadores, adaptadores, calculadoras, cables, extensiones, proyectores y routers.";
    }

    if (
        texto.includes("tiempo") ||
        texto.includes("hora") ||
        texto.includes("minuto")
    ) {
        return "En este ejemplo puedes elegir 30 minutos, 1 hora o 2 horas de uso.";
    }

    if (texto.includes("devol") || texto.includes("regresar")) {
        return "Devuelve los materiales completos y en buen estado al terminar de utilizarlos.";
    }

    if (
        texto.includes("control") ||
        texto.includes("registro") ||
        texto.includes("alumno")
    ) {
        return "Completa tu nombre, número de control, tiempo de uso, especialidad y turno.";
    }

    if (
        texto.includes("disponible") ||
        texto.includes("existencia")
    ) {
        return "La cantidad que aparece en cada tarjeta es la disponibilidad actual de la demostración.";
    }

    if (
        texto.includes("administrador") ||
        texto.includes("admin")
    ) {
        return "Abre Administrador e ingresa usuario, contraseña y el resultado del CAPTCHA.";
    }

    if (texto.includes("gracias")) {
        return "¡De nada! Estoy aquí para ayudarte.";
    }

    return "No tengo una respuesta para esa pregunta todavía. Puedes consultar el catálogo, el registro, el tiempo de uso o las devoluciones.";
}

if ($("#formChatbot")) {
    $("#formChatbot").addEventListener("submit", evento => {
        evento.preventDefault();

        const campo = $("#pregunta");
        const pregunta = campo.value.trim();

        if (!pregunta) return;

        agregarMensajeChat(pregunta, "usuario");
        agregarMensajeChat(responder(pregunta), "bot");

        campo.value = "";
        campo.focus();
    });
}

const botonAbrirChat = $("#abrirChat");
const botonCerrarChat = $("#cerrarChat");
const ventanaChat = $("#chatbot");

if (botonAbrirChat && ventanaChat) {
    botonAbrirChat.addEventListener("click", () => {
        ventanaChat.hidden = !ventanaChat.hidden;

        botonAbrirChat.setAttribute(
            "aria-expanded",
            String(!ventanaChat.hidden)
        );

        if (!ventanaChat.hidden && $("#pregunta")) {
            $("#pregunta").focus();
        }
    });
}

if (botonCerrarChat && ventanaChat) {
    botonCerrarChat.addEventListener("click", () => {
        ventanaChat.hidden = true;

        if (botonAbrirChat) {
            botonAbrirChat.setAttribute("aria-expanded", "false");
        }
    });
}

/* TEMAS */
const temas = ["guinda", "azul", "verde", "claro"];

function cambiarTema(tema) {
    if (!temas.includes(tema)) tema = "guinda";

    document.body.classList.remove(
        ...temas.map(nombre => `tema-${nombre}`)
    );

    document.body.classList.add(`tema-${tema}`);

    document.querySelectorAll(".tema-btn").forEach(boton => {
        const activo = boton.dataset.tema === tema;

        boton.classList.toggle("activo", activo);
        boton.setAttribute("aria-pressed", String(activo));
    });

    try {
        localStorage.setItem("temaCETis", tema);
    } catch (error) {}
}

document.querySelectorAll(".tema-btn").forEach(boton => {
    boton.addEventListener("click", () => cambiarTema(boton.dataset.tema));
});

try {
    cambiarTema(localStorage.getItem("temaCETis") || "guinda");
} catch (error) {
    cambiarTema("guinda");
}

mostrarSeleccion();
actualizarCatalogo();
sincronizarInventarioServidor();