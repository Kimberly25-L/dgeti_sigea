/* =========================================================
   alumno.js · Consulta del alumno (solo lectura) + asistente
   Los datos vienen de MySQL a través de api.php
   ========================================================= */
guardia("alumno").then(async () => {
    try {
        const r = await api("a_resumen");
        const a = r.alumno, cal = r.calificaciones;

        document.getElementById("nombreAlumno").textContent = a.nombre;
        document.getElementById("controlAlumno").textContent = a.numero_control;
        document.getElementById("grupoAlumno").textContent = a.grupo || "—";
        document.getElementById("espAlumno").textContent = a.especialidad || "—";

        const suma = cal.reduce((s, c) => s + Number(c.calificacion), 0);
        const prom = cal.length ? (suma / cal.length).toFixed(2) : "0.00";
        const aprob = cal.filter(c => c.calificacion >= 6).length;
        document.getElementById("promedioGeneral").textContent = prom;
        document.getElementById("promedioFinal").textContent = prom;
        document.getElementById("numMaterias").textContent = cal.length;
        document.getElementById("numAprobadas").textContent = aprob;
        document.getElementById("numeroReportes").textContent = r.reportes.length;

        document.getElementById("tablaCalificaciones").innerHTML = cal.map(c => {
            const ok = c.calificacion >= 6;
            return `<tr><td><strong>${esc(c.materia)}</strong></td><td>${esc(c.docente)}</td><td>${esc(c.periodo)}</td>
                <td class="calificacion">${Number(c.calificacion).toFixed(1)}</td>
                <td><span class="estado ${ok ? "aprobado" : "reprobado"}">${ok ? "Aprobada" : "Reprobada"}</span></td></tr>`;
        }).join("") || `<tr><td colspan="5">Aún no tienes calificaciones registradas.</td></tr>`;

        document.getElementById("listaReportes").innerHTML = r.reportes.map(p =>
            `<div class="reporte"><strong>${esc(p.tipo)}</strong> · ${esc(p.docente)}<br>${esc(p.descripcion)}</div>`).join("")
            || "<p>Sin reportes. ¡Excelente disciplina!</p>";

        const esp = a.especialidad || "tu especialidad";
        iniciarChat(["Ideas de " + esp, "Proyecto fácil y rápido", "Proyecto con inteligencia artificial", "Proyecto de electrónica"]);
    } catch (e) { aviso(e.message, "mal"); }
});
