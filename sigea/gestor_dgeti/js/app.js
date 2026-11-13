/* =========================================================
   app.js · Funciones compartidas por todas las páginas
   ========================================================= */

/* >>> los nombres completos de los integrantes <<< */
const AUTORES = ['Miguel Angel Barcenas Gonzales - Cinthya Jaquelinne Castro Ramirez - Irving Gamiño Mejia - Kimberly Paola Lara Andrade'];

/* Llamada al backend */
async function api(accion, datos, metodo) {
    const opciones = { credentials: "same-origin" };
    if (datos !== undefined) {
        opciones.method = metodo || "POST";
        opciones.headers = { "Content-Type": "application/json" };
        opciones.body = JSON.stringify(datos);
    }
    let r;
    try { r = await fetch("api/api.php?action=" + accion, opciones); }
    catch (e) { throw new Error("No hay conexión con el servidor (¿está encendido Apache/MySQL?)."); }
    let j;
    try { j = await r.json(); } catch (e) { throw new Error("Respuesta inválida del servidor."); }
    if (!j.ok) throw new Error(j.error || "Error desconocido");
    return j;
}

/* Evita inyectar HTML con datos escritos por usuarios */
const esc = s => String(s ?? "").replace(/[&<>"']/g, c =>
    ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));

function aviso(texto, tipo) {
    const t = document.createElement("div");
    t.className = "toast " + (tipo || "");
    t.textContent = texto;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 3200);
}

/* Captcha */
function recargarCaptcha() {
    const img = document.getElementById("captchaImg");
    if (img) img.src = "api/api.php?action=captcha&t=" + Date.now();
    const campo = document.getElementById("captcha");
    if (campo) campo.value = "";
}

/* Protege los portales: si no hay sesión del rol correcto, regresa al login */
async function guardia(rol) {
    try {
        const r = await api("me");
        if (r.u.rol !== rol) throw new Error();
        return r.u;
    } catch (e) {
        location.href = "login.html?rol=" + rol;
        return new Promise(() => {});   // detiene la carga de la página
    }
}

async function cerrarSesion() {
    try { await api("logout", {}); } catch (e) {}
    location.href = "menu_idx.html";
}

/* Temas de color (botones, tarjetas y encabezados cambian al instante) */
const TEMAS = {
    institucional: ["#b32424", "#08472e", "#eaf6f0"],
    verde:         ["#16834b", "#0b5d3b", "#eaf6f0"],
    dorado:        ["#a36a14", "#76591e", "#fffaf0"],
    azul:          ["#2563eb", "#1d4ed8", "#eff6ff"]
};
function aplicarTema(nombre) {
    const t = TEMAS[nombre] || TEMAS.institucional, r = document.documentElement.style;
    r.setProperty("--azul", t[0]); r.setProperty("--azul-oscuro", t[1]); r.setProperty("--azul-claro", t[2]);
    localStorage.setItem("tema", nombre);
}

document.addEventListener("DOMContentLoaded", function () {
    aplicarTema(localStorage.getItem("tema") || "institucional");

    const sel = document.createElement("div");
    sel.className = "selector-tema";
    sel.title = "Cambiar color del sistema";
    Object.keys(TEMAS).forEach(n => {
        const b = document.createElement("button");
        b.style.background = TEMAS[n][0];
        b.setAttribute("aria-label", "Tema " + n);
        b.onclick = () => aplicarTema(n);
        sel.appendChild(b);
    });
    document.body.appendChild(sel);

    let pie = document.querySelector("footer");
    if (!pie) { pie = document.createElement("footer"); document.body.appendChild(pie); }
    pie.innerHTML = "SIGEA · " + new Date().getFullYear() +
        " · <strong>Derechos Reservados</strong><br><strong>Elaborado por:</strong> " +
        AUTORES.map(esc).join(" · ");
});
