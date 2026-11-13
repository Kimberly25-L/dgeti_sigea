<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGEA | Rumbo a la titulación</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="imagenes/icono.ico" type="image/x-icon">
</head>

<?php include "chatbot.php"; ?>

<body>

    <header>

        <div class="logo">

            <div class="logo-jaguar">
                <img src="imagenes/logo.png" alt="Logo del CETis 120">
            </div>

            <div class="logo-text">
                <h2>CETis 120</h2>
                <p>
                    Centro de Estudios Tecnológicos industrial y de servicios 120
                </p>
            </div>

        </div>

        <nav class="menu-superior">

            <a href="../menu.html">
                Menu principal
            </a>

            <div class="separador"></div>

            <a href="contacto.php">
                <span>✉</span>
                Contacto
            </a>

            <div class="separador"></div>
            
            <a href="login.php">
                <span>👤</span>
                Administrador
            </a>


        </nav>

    </header>

    <main>

        <section class="hero">

            <h1>
                Rumbo a la Titulación
            </h1>

            <p>
                Conoce las modalidades de titulación disponibles
                y descubre el proceso que debes seguir para alcanzar
                tu título profesional.
            </p>

        </section>             

        <section class="modalidades">
            <a href="promedio.php" class="tarjeta promedio">
                <div class="contenido-tarjeta">
                    <h2>Promedio</h2>

                    <p>
                        Conoce los requisitos y pasos necesarios
                        para realizar tu proceso de titulación
                        mediante promedio.
                    </p>

                    <span class="boton">
                        VER PROCESO →
                    </span>
                </div>
            </a>

            <a href="prototipo.php" class="tarjeta prototipo">
                <div class="contenido-tarjeta">
                    <h2>Prototipo / Proyecto</h2>
                    <p>
                        Descubre el proceso para desarrollar, presentar y completar tu proyecto de titulación.
                    </p>
                    <span class="boton">
                        VER PROCESO →
                    </span>
                </div>
            </a>

            <a href="experienciaLaboral.php" class="tarjeta experiencia">

                <div class="contenido-tarjeta">

                    <h2>Experiencia Laboral</h2>

                    <p>
                        Consulta los requisitos y el procedimiento
                        para acreditar tu experiencia laboral.
                    </p>

                    <span class="boton">
                        VER PROCESO →
                    </span>

                </div>

            </a>

        </section>

    </main>

    <footer>

        <p>
            © 2026 SIGEA &nbsp;·&nbsp; Proceso de Titulación
        </p>

        <p class="autores">
            Miguel Angel Barcenas Gonzales - Cinthya Jaquelinne Castro Ramirez - Irving Gamiño Mejia - Kimberly Paola Lara Andrade  
        </p>

    </footer>

</body>
</html>