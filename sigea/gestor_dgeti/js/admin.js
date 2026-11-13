/* =========================================================
   admin.js · El administrador da de alta alumnos, maestros,
   grupos y materias (se guardan en MySQL).
   Solo los alumnos/maestros dados de alta pueden crear cuenta.
   ========================================================= */
const TIPOS = {
    alumno:  { t: "Alumnos",  campos: [["numero_control", "Número de control"], ["nombre", "Nombre completo"], ["grupo", "Grupo", "grupos"], ["especialidad", "Especialidad"]],
               cols: [["numero_control", "N° Control"], ["nombre", "Nombre"], ["grupo", "Grupo"], ["especialidad", "Especialidad"], ["registrado", "Cuenta"]] },
    maestro: { t: "Maestros", campos: [["rfc", "RFC (13 caracteres)"], ["nombre", "Nombre completo"], ["especialidad", "Especialidad"]],
               cols: [["rfc", "RFC"], ["nombre", "Nombre"], ["especialidad", "Especialidad"], ["registrado", "Cuenta"]] },
    grupo:   { t: "Grupos",   campos: [["nombre", "Nombre (ej. 5°A)"], ["especialidad", "Especialidad"], ["semestre", "Semestre", "number"]],
               cols: [["nombre", "Grupo"], ["especialidad", "Especialidad"], ["semestre", "Semestre"]] },
    materia: { t: "Materias", campos: [["nombre", "Nombre de la materia"]], cols: [["nombre", "Materia"]] }
};
let tipoActual = "alumno";
let listaGrupos = [];

async function cargarTab(tipo) {
    tipoActual = tipo;
    document.querySelectorAll(".tab").forEach(b => b.classList.toggle("activa", b.dataset.tipo === tipo));
    const T = TIPOS[tipo];
    document.getElementById("tituloForm").textContent = "Alta de " + T.t.toLowerCase();

    if (tipo === "alumno") {
        try { listaGrupos = (await api("admin_list&tipo=grupo")).datos.map(g => g.nombre); } catch (e) {}
    }

    document.getElementById("camposForm").innerHTML = T.campos.map(c => {
        const [n, l, extra] = c;
        if (extra === "grupos")
            return `<div><label>${l}</label><select id="f_${n}" class="control-formulario"><option value="">— Sin grupo —</option>${listaGrupos.map(g => `<option>${esc(g)}</option>`).join("")}</select></div>`;
        return `<div><label>${l}</label><input id="f_${n}" class="control-formulario" type="${extra || "text"}" ${n === "nombre" || n === "numero_control" || n === "rfc" ? "required" : ""}></div>`;
    }).join("");

    await cargarTabla();
}

async function cargarTabla() {
    const T = TIPOS[tipoActual];
    document.getElementById("tituloTabla").textContent = T.t + " registrados";
    document.getElementById("encabezado").innerHTML = T.cols.map(c => `<th>${c[1]}</th>`).join("") + "<th>Acción</th>";
    const cuerpo = document.getElementById("tablaAdmin");
    try {
        const r = await api("admin_list&tipo=" + tipoActual);
        cuerpo.innerHTML = r.datos.map(f => "<tr>" + T.cols.map(c => {
            if (c[0] === "registrado")
                return `<td><span class="estado ${f.registrado == 1 ? "aprobado" : "pendiente"}">${f.registrado == 1 ? "Activada" : "Pendiente"}</span></td>`;
            return `<td>${esc(f[c[0]] ?? "—")}</td>`;
        }).join("") + `<td><button class="btn-mini btn-peligro" onclick="eliminar(${f.id})">Eliminar</button></td></tr>`).join("")
            || `<tr><td colspan="9">Aún no hay registros.</td></tr>`;
    } catch (e) { aviso(e.message, "mal"); }
}

async function eliminar(id) {
    if (!confirm("¿Eliminar este registro? Si tiene cuenta, también se borrará.")) return;
    try { await api("admin_delete", { tipo: tipoActual, id: id }); aviso("Registro eliminado", "ok"); cargarTabla(); }
    catch (e) { aviso(e.message, "mal"); }
}

document.getElementById("formAdmin").addEventListener("submit", async function (e) {
    e.preventDefault();
    const datos = {};
    TIPOS[tipoActual].campos.forEach(c => datos[c[0]] = document.getElementById("f_" + c[0]).value);
    try {
        await api("admin_add", { tipo: tipoActual, datos: datos });
        aviso("Registro guardado en la base de datos", "ok");
        cargarTab(tipoActual);
    } catch (err) { aviso(err.message, "mal"); }
});

document.querySelectorAll(".tab").forEach(b => b.onclick = () => cargarTab(b.dataset.tipo));
guardia("admin").then(u => {
    document.getElementById("nombreSesion").textContent = u.nombre;
    cargarTab("alumno");
});
