/* =========================================================
   bot.js · Chat del asistente "Jaguares" (ideas de proyectos)
   Las respuestas salen de la tabla conocimiento_bot.
   ========================================================= */
function iniciarChat(sugerencias) {
    const chat = document.getElementById("chat");
    const campo = document.getElementById("mensajeBot");
    const chips = document.getElementById("chips");

    function pinta(clase, html) {
        const m = document.createElement("div");
        m.className = "msg " + clase;
        m.innerHTML = html;
        chat.appendChild(m);
        chat.scrollTop = chat.scrollHeight;
    }

    async function enviar(texto) {
        texto = (texto || campo.value).trim();
        if (!texto) return;
        campo.value = "";
        pinta("yo", esc(texto));
        try {
            const r = await api("bot_chat", { mensaje: texto });
            let h = esc(r.respuesta);
            r.ideas.forEach(i => { h += `<div class="idea"><strong>${esc(i.tema)}</strong><br>${esc(i.contenido)}</div>`; });
            pinta("bot", h);
        } catch (e) { pinta("bot", "😵 " + esc(e.message)); }
    }

    document.getElementById("btnEnviarBot").onclick = () => enviar();
    campo.addEventListener("keydown", e => { if (e.key === "Enter") enviar(); });
    (sugerencias || []).forEach(s => {
        const c = document.createElement("button");
        c.className = "chip"; c.textContent = s;
        c.onclick = () => enviar(s);
        chips.appendChild(c);
    });
    pinta("bot", "¡Hola! Somos <strong>Jaguares</strong> 🐾, tus asistentes de proyectos. Dime qué área te interesa y te doy ideas.");
}
