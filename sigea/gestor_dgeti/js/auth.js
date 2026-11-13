/* =========================================================
   auth.js · Login y registro (con captcha)
   ========================================================= */
const ROLES = {
    alumno:    { t: "Alumno",                 i: "👨‍🎓", id: "Número de control", ph: "Ej. 220502",        dest: "portalalumno.html",   reg: true },
    maestro:   { t: "Maestro",                i: "👨‍🏫", id: "RFC",               ph: "Ej. GOMA800101ABC", dest: "portalmaestro.html",  reg: true },
    admin:     { t: "Administrador",          i: "⚙️",   id: "Usuario",           ph: "Ej. ADMIN001",      dest: "portaladmin.html",    reg: false },
    admin_bot: { t: "Admin del asistente",    i: "🤖",   id: "Usuario",           ph: "Ej. BOT001",        dest: "portaladminbot.html", reg: false }
};

function mostrarError(texto, ok) {
    const m = document.getElementById("mensajeError");
    m.textContent = texto;
    m.className = "mensaje-error" + (ok ? " mensaje-ok" : "");
}

/* ---------- LOGIN ---------- */
const formLogin = document.getElementById("formLogin");
if (formLogin) {
    const rol = ROLES[new URLSearchParams(location.search).get("rol")] ? new URLSearchParams(location.search).get("rol") : "alumno";
    const R = ROLES[rol];
    document.getElementById("icono").textContent = R.i;
    document.getElementById("badge").textContent = "ACCESO · " + R.t.toUpperCase();
    document.getElementById("lblId").textContent = R.id;
    document.getElementById("identificador").placeholder = R.ph;
    if (!R.reg) document.getElementById("linkRegistro").classList.add("oculto");
    recargarCaptcha();

    formLogin.addEventListener("submit", async function (e) {
        e.preventDefault();
        mostrarError("");
        try {
            await api("login", {
                rol: rol,
                identificador: document.getElementById("identificador").value,
                password: document.getElementById("password").value,
                captcha: document.getElementById("captcha").value
            });
            location.href = R.dest;
        } catch (err) { mostrarError(err.message); recargarCaptcha(); }
    });
}

/* ---------- REGISTRO ---------- */
const formRegistro = document.getElementById("formRegistro");
if (formRegistro) {
    const selRol = document.getElementById("rol");
    function ajusta() {
        const R = ROLES[selRol.value];
        document.getElementById("lblId").textContent = R.id;
        document.getElementById("identificador").placeholder = R.ph;
    }
    selRol.addEventListener("change", ajusta);
    ajusta(); recargarCaptcha();

    formRegistro.addEventListener("submit", async function (e) {
        e.preventDefault();
        mostrarError("");
        if (document.getElementById("password").value !== document.getElementById("password2").value)
            return mostrarError("Las contraseñas no coinciden.");
        try {
            await api("register", {
                rol: selRol.value,
                identificador: document.getElementById("identificador").value,
                nombre: document.getElementById("nombre").value,
                correo: document.getElementById("correo").value,
                password: document.getElementById("password").value,
                captcha: document.getElementById("captcha").value
            });
            mostrarError("✅ Cuenta activada. Redirigiendo al inicio de sesión…", true);
            setTimeout(() => location.href = "login.html?rol=" + selRol.value, 1500);
        } catch (err) { mostrarError(err.message); recargarCaptcha(); }
    });
}
