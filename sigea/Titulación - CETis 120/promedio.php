<?php
require_once "conexion.php";
require_once "captcha_lib.php";
require_once "captcha_widget.php";

$alumno = null;
$errorConsulta = "";
$numeroControl = "";

function escapar($valor) {
    return htmlspecialchars(
        (string)($valor ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}


function estadoDocumento($valor) {
    if ($valor === null || $valor === "") {
        return "Sin información registrada";
    }

    if ((string)$valor === "1") {
        return "Entregado";
    }

    if ((string)$valor === "0") {
        return "Pendiente";
    }

    return (string)$valor;
}


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $numeroControl = trim($_POST["numero_control"] ?? "");

    if ($numeroControl === "") {
        $errorConsulta = "Ingresa tu número de control.";
    } elseif (!captcha_validar("consulta", $_POST["captcha"] ?? "")) {
        $errorConsulta = "El captcha es incorrecto o expiró. Intenta con el código nuevo.";
    } else {

        $modalidades = [
            "alumnos_promedio" => "Promedio",
            "alumnos_prototipo" => "Prototipo",
            "alumnos_experiencia" => "Experiencia Laboral"
        ];

        try {
            foreach ($modalidades as $tabla => $modalidad) {

                $campoPromedio = (
                    $tabla === "alumnos_promedio"
                ) ? ", promedio" : "";

                $sql = "SELECT
                            no_control,
                            nombre,
                            apellidos,
                            estado,
                            doc_identidad,
                            doc_escolares
                            $campoPromedio
                        FROM `$tabla`
                        WHERE no_control = ?
                        LIMIT 1";

                $stmt = $conexion->prepare($sql);
                $stmt->bind_param("s", $numeroControl);
                $stmt->execute();

                $resultado = $stmt->get_result();
                $fila = $resultado->fetch_assoc();

                $stmt->close();

                if ($fila) {
                    $alumno = $fila;
                    $alumno["modalidad"] = $modalidad;
                    break;
                }
            }

            if (!$alumno) {
                $errorConsulta =
                    "No encontramos un alumno con ese número "
                    . "de control. Verifica tus datos.";
            }

        } catch (mysqli_sql_exception $e) {

            error_log(
                "Error en consulta de titulación: "
                . $e->getMessage()
            );

            $errorConsulta =
                "Ocurrió un error al consultar los datos. "
                . "Inténtalo nuevamente más tarde.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="style.css">

    <title>Titulación | Promedio</title>

    <link rel="icon" href="imagenes/icono.ico" type="image/x-icon">

</head>

<?php include "chatbot.php"; ?>

<body class="pagina-promedio">

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

        <!-- MENÚ SUPERIOR DERECHO -->

        <nav class="menu-superior">

            <a href="principal.php">
                <span>⌂</span>
                Inicio
            </a>

            <div class="separador"></div>

            <a href="contacto.php">
                <span>✉</span>
                Contacto
            </a>

        </nav>

    </header>


    <main>

        <!-- MENÚ DE MODALIDADES -->

        <section id="boton">

            <a href="promedio.php">
                <span class="botonInicio activo">
                    PROMEDIO
                </span>
            </a>

            <a href="prototipo.php">
                <span class="botonInicio">
                    PROTOTIPO/PROYECTO
                </span>
            </a>

            <a href="experienciaLaboral.php">
                <span class="botonInicio">
                    EXPERIENCIA LABORAL
                </span>
            </a>

        </section>


        <!-- TÍTULO -->

        <section class="hero">

            <div class="icono-titulo">
                🎓
            </div>

            <div class="titulo-texto">

                <h1>
                    PROMEDIO
                </h1>

                <p>
                    Recuerda que para poder titularte por promedio debes contar
                    con un promedio mínimo de 8.0 o más en todos tus submódulos.
                </p>

                <div class="linea-dorada"></div>

            </div>

        </section>


        <!-- REQUISITOS -->

        <section class="requisitos-container">

            <div class="requisitos">

                <div class="titulo-requisitos">

                    <div class="icono-requisitos">
                        ▤
                    </div>

                    <h2>
                        REQUISITOS
                    </h2>

                </div>


                <div class="contenido-requisitos">

                    <!-- COLUMNA 1 -->

                    <div class="columna-requisitos">

                        <div class="requisito">
                            <span>✓</span>
                            <p>
                                2 hojas de recepción
                                <small>
                                    (Datos personales, datos escolares)
                                </small>
                            </p>
                        </div>

                        <div class="requisito">
                            <span>✓</span>
                            <p>
                                Original y 2 copias de tu Acta de Nacimiento
                            </p>
                        </div>

                        <div class="requisito">
                            <span>✓</span>
                            <p>
                                Original y 2 copias de tu CURP
                            </p>
                        </div>

                        <div class="requisito">
                            <span>✓</span>
                            <p>
                                Original y 2 copias de tu Certificado de Secundaria
                            </p>
                        </div>

                        <div class="requisito">
                            <span>✓</span>
                            <p>
                                Original y 2 copias de tu Certificado de Inicio de Bachillerato
                            </p>
                        </div>

                    </div>


                    <!-- COLUMNA 2 -->

                    <div class="columna-requisitos">

                        <div class="requisito">
                            <span>✓</span>
                            <p>
                                Original y 2 copias de tu Certificado de Terminación de Bachillerato
                            </p>
                        </div>

                        <div class="requisito">
                            <span>✓</span>
                            <p>
                                Original y 1 copia de tu Servicio Social
                            </p>
                        </div>

                        <div class="requisito">
                            <span>✓</span>
                            <p>
                                Original y 1 copia de tus Prácticas
                            </p>
                        </div>

                        <div class="requisito">
                            <span>✓</span>
                            <p>
                                Original y 2 copias de cada módulo
                            </p>
                        </div>

                        <div class="requisito">
                            <span>✓</span>
                            <p>
                                Original y 2 copias de Pago por concepto de Titulación
                            </p>
                        </div>

                        <div class="requisito">
                            <span>✓</span>
                            <p>
                                2 fotos pequeñas y 6 grandes
                            </p>
                        </div>

                    </div>


                    <!-- DESCARGA -->

                    <div class="descargas">

                        <div class="icono-documento">
                            ▤
                        </div>

                        <p>
                            Descarga aquí el formato de solicitud
                            y guías de apoyo.
                        </p>

                        <a href="#" class="boton-descargar">
                            ↓ &nbsp; DESCARGAR
                        </a>

                        <p class="frase">
                            "Tu esfuerzo de hoy,<br>
                            es tu título mañana."
                        </p>

                        <div class="decoracion"></div>

                    </div>

                </div>

            </div>

        </section>


        <!-- CONSULTA: -->

        <section class="consulta-container">

            <div class="consulta">

                <div class="consulta-info">

                    <div class="icono-consulta">
                        ⌕
                    </div>

                    <div>

                        <h2>
                            CONSULTA TU PROGRESO
                        </h2>

                        <div class="linea-consulta"></div>

                        <p>
                            Ingresa tu número de control para ver el estado
                            de tu proceso de titulación por promedio.
                        </p>

                    </div>

                </div>


                <div class="consulta-form">

                    <form method="POST" action="promedio.php">

                        <label for="numero-control">
                            Número de control:
                        </label>

                        <?php captcha_campo("consulta"); ?>

                        <div class="formulario">

                            <input
                                type="text"
                                id="numero-control"
                                name="numero_control"
                                placeholder="Ej. 20231234"
                                value="<?= escapar($numeroControl) ?>"
                                maxlength="30"
                                required
                            >

                            <button type="submit">
                                ⌕ &nbsp; CONSULTAR
                            </button>

                        </div>

                    </form>

                    <small>
                        ● &nbsp; Podrás ver tu avance, documentos entregados
                        y próximos pasos.
                    </small>

                </div>

            </div>

        </section>


        <!-- CONSULTA -->

        <?php if ($errorConsulta !== ""): ?>

            <section class="resultado-consulta mensaje-consulta" role="alert">

                <h2>No se pudo completar la consulta</h2>

                <p>
                    <?= escapar($errorConsulta) ?>
                </p>

            </section>

        <?php endif; ?>


        <?php if ($alumno): ?>

            <section class="resultado-consulta">

                <h2>Información del alumno</h2>

                <span class="modalidad-alumno">
                    <?= escapar($alumno["modalidad"]) ?>
                </span>

                <div class="datos-alumno">

                    <div class="dato-alumno">
                        <strong>Nombre completo</strong>
                        <span>
                            <?= escapar(
                                $alumno["nombre"] . " " . $alumno["apellidos"]
                            ) ?>
                        </span>
                    </div>

                    <div class="dato-alumno">
                        <strong>Número de control</strong>
                        <span>
                            <?= escapar($alumno["no_control"]) ?>
                        </span>
                    </div>

                    <?php if ($alumno["modalidad"] === "Promedio"): ?>

                        <div class="dato-alumno">
                            <strong>Promedio registrado</strong>
                            <span>
                                <?= escapar($alumno["promedio"]) ?>
                            </span>
                        </div>

                    <?php endif; ?>

                    
                    <div class="dato-alumno">
                        <strong>Estado del proceso</strong>
                        <span class="estado <?php
                            echo (
                                strtolower(trim($alumno["estado"])) === "terminado"
                            ) ? "estado-terminado" : "estado-proceso";
                        ?>">
                            <?= escapar($alumno["estado"]) ?>
                        </span>
                    </div>

                <div class="documentos-alumno">

                <h3>Documentación</h3>

                    <div class="documento-alumno">
                        <strong>Documento de identidad</strong>
                        <p>
                            <?php
                            $identidad = estadoDocumento($alumno["doc_identidad"]);
                            $claseIdentidad = (
                                $identidad === "Entregado"
                            ) ? "documentos-si" : "documentos-no";
                            ?>
                            <span class="<?= escapar($claseIdentidad) ?>">
                                <?= escapar($identidad) ?>
                            </span>
                        </p>
                    </div>

                    <div class="documento-alumno">
                        <strong>Documentos escolares</strong>
                        <p>
                            <?php
                            $escolares = estadoDocumento($alumno["doc_escolares"]);
                            $claseEscolares = (
                                $escolares === "Entregado"
                            ) ? "documentos-si" : "documentos-no";
                            ?>
                            <span class="<?= escapar($claseEscolares) ?>">
                                <?= escapar($escolares) ?>
                            </span>
                        </p>
                    </div>

                </div>


            </section>

        <?php endif; ?>

    </main>


    <footer>

        <p>
            © 2026 SIGEA · &nbsp; Proceso de Titulación
        </p>

        <p class="autores">
            Elaborado por: Miguel Angel Barcenas Gonzales - Cinthya Jaquelinne Castro Ramirez - Irving Gamiño Mejia - Kimberly Paola Lara Andrade  
        </p>

    </footer>

</body>
</html>
