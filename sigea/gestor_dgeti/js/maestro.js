/* =========================================================
   maestro.js · Calificaciones y reportes (guardados en MySQL)
   ========================================================= */
let alumnosCargados = [];

function nombreMateria() {
    const s = document.getElementById("asignatura");
    return s.options[s.selectedIndex]?.text || "";
}

async function cargarGrupo() {
    const grupo = document.getElementById("grupo").value;
    const materia = document.getElementById("asignatura").value;
    if (!grupo || !materia) return aviso("Selecciona materia y grupo", "mal");
    try {
        const r = await api("m_alumnos&grupo=" + encodeURIComponent(grupo) + "&materia=" + materia);
        alumnosCargados = r.alumnos;
        document.getElementById("tituloGrupo").textContent = grupo + " · " + nombreMateria();
        document.getElementById("cuerpoAlumnos").innerHTML = r.alumnos.map(a => `
            <tr data-id="${a.id}">
                <td>${esc(a.numero_control)}</td>
                <td><strong>${esc(a.nombre)}</strong></td>
                <td><input type="number" class="input-tabla" min="0" max="10" step="0.1" value="${a.calificacion ?? ""}"></td>
                <td>${a.reportes} reporte(s)</td>
            </tr>`).join("") || `<tr><td colspan="4">No hay alumnos dados de alta en este grupo.</td></tr>`;
        document.getElementById("selAlumnoRep").innerHTML = r.alumnos.map(a => `<option value="${a.id}">${esc(a.nombre)}</option>`).join("");
        document.getElementById("seccionAlumnos").classList.remove("oculto");
    } catch (e) { aviso(e.message, "mal"); }
}

async function guardarCambios() {
    const items = [...document.querySelectorAll("#cuerpoAlumnos tr[data-id]")].map(f => ({
        alumno_id: f.dataset.id,
        calificacion: f.querySelector("input").value
    }));
    try {
        const r = await api("m_guardar", { materia: document.getElementById("asignatura").value, items: items });
        aviso(r.guardados + " calificación(es) guardada(s)", "ok");
    } catch (e) { aviso(e.message, "mal"); }
}

async function enviarReporte() {
    const desc = document.getElementById("descReporte").value.trim();
    if (!document.getElementById("selAlumnoRep").value) return aviso("Primero carga un grupo y elige al alumno", "mal");
    if (!desc) return aviso("Escribe la descripción del reporte", "mal");
    try {
        await api("m_reporte", {
            alumno_id: document.getElementById("selAlumnoRep").value,
            tipo: document.getElementById("tipoReporte").value,
            descripcion: desc
        });
        document.getElementById("descReporte").value = "";
        aviso("Reporte registrado", "ok");
        cargarGrupo();
    } catch (e) { aviso(e.message, "mal"); }
}

guardia("maestro").then(async () => {
    try {
        const c = await api("m_contexto");
        document.getElementById("nombreMaestro").textContent = c.nombre;
        document.getElementById("espMaestro").textContent = c.especialidad || "—";
        document.getElementById("periodo").textContent = c.periodo;
        document.getElementById("asignatura").innerHTML = c.materias.map(m => `<option value="${m.id}">${esc(m.nombre)}</option>`).join("");
        document.getElementById("grupo").innerHTML = c.grupos.map(g => `<option>${esc(g)}</option>`).join("");
    } catch (e) { aviso(e.message, "mal"); }
});
