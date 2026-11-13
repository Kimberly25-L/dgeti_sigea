/* SIGEA · menú principal */
document.addEventListener("DOMContentLoaded", function () {

    /* Año del pie de página */
    const anio = document.getElementById("anio");
    if (anio) anio.textContent = new Date().getFullYear();

    /* Aviso flotante */
    const aviso = document.getElementById("aviso");
    let t;
    function mostrarAviso(texto) {
        if (!aviso) return;
        aviso.textContent = texto;
        aviso.classList.add("visible");
        clearTimeout(t);
        t = setTimeout(() => aviso.classList.remove("visible"), 2600);
    }

    /* La mascota responde al hacer clic */
    const frases = [
        "¡Los jaguares cazamos ideas en equipo! 💡",
        "¿Listo para tu parcial? ¡Éxito! 🎓",
        "Elige un módulo y te acompañamos 🐾",
        "Del aula a tu título, contigo en cada paso 🚀"
    ];
    
    let n = 0;
    const mascota = document.getElementById("mascota");
    const burbuja = document.getElementById("burbuja");
    if (mascota && burbuja) {
        mascota.addEventListener("click", function () {
            burbuja.textContent = frases[n++ % frases.length];
            burbuja.classList.remove("rebote");
            void burbuja.offsetWidth;          // reinicia la animación
            burbuja.classList.add("rebote");
        });
    }

    /* Aviso al entrar a un módulo */
    document.querySelectorAll(".boton").forEach(function (b) {
        b.addEventListener("click", function () { mostrarAviso("Abriendo módulo…"); });
    });
});
