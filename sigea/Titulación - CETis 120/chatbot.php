<div class="chatbot">
  
    <button class="boton-chat" id="botonChat">
        <img src="imagenes/jaguar-chatbot.png" alt="Abrir asistente virtual">
    </button>

    <div class="ventana-chat" id="ventanaChat">

        <div class="encabezado-chat">
            <div>
                <strong>Jaguares · CETis 120</strong>
                <small>Proceso de Titulación</small>
            </div>

            <button id="cerrarChat">×</button>
        </div>

        <div class="mensajes-chat" id="mensajesChat">

            <div class="mensaje ia">
                ¡Hola! 👋<br>
                Somos Jaguares, los asistentes del proceso de titulación.
                ¿En qué puedo ayudarte?
            </div>

        </div>

        <form class="formulario-chat" id="formularioChat">

            <input
                type="text"
                id="entradaChat"
                placeholder="Escribe tu pregunta..."
                autocomplete="off"
            >

            <button type="submit">
                ➤
            </button>

        </form>

    </div>

</div>

<style>

.chatbot {
    position: fixed;
    right: 25px;
    bottom: 25px;
    z-index: 9999;
    font-family: Arial, Helvetica, sans-serif;
}

.boton-chat {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    border: none;
    background: #751912;
    color: white;
    font-size: 28px;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(0,0,0,.25);
}

.boton-chat img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    border-radius: 50%;
    display: block;
}

.ventana-chat {
    display: none;
    position: absolute;
    right: 0;
    bottom: 75px;

    width: 350px;
    height: 480px;

    background: white;
    border-radius: 15px;
    overflow: hidden;

    box-shadow: 0 5px 25px rgba(0,0,0,.25);
}

.encabezado-chat {
    height: 65px;
    padding: 0 15px;

    background: #9B231B;
    color: white;

    display: flex;
    align-items: center;
    justify-content: space-between;
}

.encabezado-chat strong {
    display: block;
    font-size: 16px;
}

.encabezado-chat small {
    font-size: 12px;
}

.encabezado-chat button {
    border: none;
    background: none;
    color: white;
    font-size: 25px;
    cursor: pointer;
}

.mensajes-chat {
    height: 350px;
    padding: 15px;

    overflow-y: auto;
    background: #F5EFE6;
}

.mensaje {
    max-width: 80%;
    padding: 10px 13px;
    margin-bottom: 10px;
    border-radius: 12px;
    font-size: 14px;
    line-height: 1.4;
}

.mensaje.ia {
    background: #EDF3F8;
    color: #123F70;
}

.mensaje.usuario {
    margin-left: auto;
    background: #104587;
    color: white;
}

.formulario-chat {
    height: 65px;
    padding: 10px;

    display: flex;
    gap: 8px;

    border-top: 1px solid #ddd;
    background: white;
}

.formulario-chat input {
    flex: 1;

    border: 1px solid #ccc;
    border-radius: 20px;

    padding: 10px 15px;
    outline: none;
}

.formulario-chat button {
    width: 42px;
    border: none;
    border-radius: 50%;

    background: #104587;
    color: white;

    font-size: 18px;
    cursor: pointer;
}

@media (max-width: 500px) {

    .chatbot {
        right: 15px;
        bottom: 15px;
    }

    .ventana-chat {
        width: calc(100vw - 30px);
        height: 450px;
    }
}

</style>

<script>

const botonChat = document.getElementById("botonChat");
const ventanaChat = document.getElementById("ventanaChat");
const cerrarChat = document.getElementById("cerrarChat");

const formularioChat = document.getElementById("formularioChat");
const entradaChat = document.getElementById("entradaChat");
const mensajesChat = document.getElementById("mensajesChat");


botonChat.addEventListener("click", function() {

    ventanaChat.style.display = "block";

});

cerrarChat.addEventListener("click", function() {

    ventanaChat.style.display = "none";

});

formularioChat.addEventListener("submit", async function(event) {

    event.preventDefault();

    const pregunta = entradaChat.value.trim();

    if (pregunta === "") {
        return;
    }

    // Mostrar pregunta del usuario
    const mensajeUsuario = document.createElement("div");

    mensajeUsuario.className = "mensaje usuario";

    mensajeUsuario.textContent = pregunta;

    mensajesChat.appendChild(mensajeUsuario);

    // Limpiar input
    entradaChat.value = "";

    const mensajeEscribiendo = document.createElement("div");

    mensajeEscribiendo.className = "mensaje ia";
    mensajeEscribiendo.textContent = "Estoy buscando la información...";

    mensajesChat.appendChild(mensajeEscribiendo);

    mensajesChat.scrollTop = mensajesChat.scrollHeight;

    // Bajar automáticamente
    mensajesChat.scrollTop = mensajesChat.scrollHeight;


    try {
        // Enviar pregunta a nuestro Asistente Virtual
        const respuesta = await fetch(
            "ia/buscar.php?pregunta=" + encodeURIComponent(pregunta)
        );

        const texto = await respuesta.text();

        mensajeEscribiendo.remove();

        // Crear mensaje del Asistente Virtual
        const mensajeIA = document.createElement("div");

        mensajeIA.className = "mensaje ia";

        mensajeIA.innerHTML = texto;

        mensajesChat.appendChild(mensajeIA);

        // Bajar automáticamente
        mensajesChat.scrollTop = mensajesChat.scrollHeight;

    } 
    
    catch (error) {

        const mensajeError = document.createElement("div");

        mensajeError.className = "mensaje ia";

        mensajeError.textContent =
            "Ocurrió un error al comunicarse con el asistente.";

        mensajesChat.appendChild(mensajeError);

    }

});

</script>