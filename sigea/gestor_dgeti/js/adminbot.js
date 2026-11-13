/* =========================================================
   adminbot.js · El segundo administrador alimenta el
   conocimiento del asistente (tabla conocimiento_bot).
   ========================================================= */
async function cargarConocimiento() {
    try {
        const r = await api("bot_list");
        window._cb = r.datos;
        document.getElementById("tablaBot").innerHTML = r.datos.map(f => `
            <tr><td><strong>${esc(f.tema)}</strong></td><td>${esc(f.pregunta_clave)}</td>
            <td>${esc(f.palabras_clave)}</td>
            <td><span class="estado ${f.activo == 1 ? "aprobado" : "pendiente"}">${f.activo == 1 ? "Activo" : "Oculto"}</span></td>
            <td><button class="btn-mini" onclick="editar(${f.id})">Editar</button>
                <button class="btn-mini btn-peligro" onclick="borrar(${f.id})">Eliminar</button></td></tr>`).join("")
            || `<tr><td colspan="5">Aún no hay conocimiento registrado.</td></tr>`;
    } catch (e) { aviso(e.message, "mal"); }
}

function editar(id) {
    const f = window._cb.find(x => x.id == id);
    document.getElementById("kId").value = f.id;
    document.getElementById("kTema").value = f.tema;
    document.getElementById("kPregunta").value = f.pregunta_clave;
    document.getElementById("kContenido").value = f.contenido;
    document.getElementById("kPalabras").value = f.palabras_clave || "";
    document.getElementById("kActivo").checked = f.activo == 1;
    document.getElementById("tituloForm").textContent = "Editar conocimiento #" + f.id;
    scrollTo({ top: 0, behavior: "smooth" });
}

function limpiar() {
    document.getElementById("formBot").reset();
    document.getElementById("kId").value = "";
    document.getElementById("kActivo").checked = true;
    document.getElementById("tituloForm").textContent = "Nuevo conocimiento";
}

async function borrar(id) {
    if (!confirm("¿Eliminar este conocimiento?")) return;
    try { await api("bot_delete", { id: id }); aviso("Eliminado", "ok"); cargarConocimiento(); }
    catch (e) { aviso(e.message, "mal"); }
}

document.getElementById("formBot").addEventListener("submit", async function (e) {
    e.preventDefault();
    try {
        await api("bot_save", {
            id: document.getElementById("kId").value,
            tema: document.getElementById("kTema").value,
            pregunta_clave: document.getElementById("kPregunta").value,
            contenido: document.getElementById("kContenido").value,
            palabras_clave: document.getElementById("kPalabras").value,
            activo: document.getElementById("kActivo").checked
        });
        aviso("Conocimiento guardado", "ok");
        limpiar(); cargarConocimiento();
    } catch (err) { aviso(err.message, "mal"); }
});

guardia("admin_bot").then(u => {
    document.getElementById("nombreSesion").textContent = u.nombre;
    cargarConocimiento();
    iniciarChat(["Ideas de programación", "Proyecto de electrónica"]);
});
