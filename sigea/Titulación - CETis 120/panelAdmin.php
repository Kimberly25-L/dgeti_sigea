<?php

session_start();

if (!isset($_SESSION["admin"])) {

    header("Location: login.php");
    exit();

}

require_once "conexion.php";
require_once "conexion_ia.php";

$seccion = $_GET['seccion'] ?? '';


/* ACCIONES DEL CHATBOT */

// GUARDAR O MODIFICAR UN CONOCIMIENTO
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    in_array(
        $_POST["accion"] ?? "",
        ["guardar_conocimiento", "modificar_conocimiento"],
        true
    )
) {
    $transaccionIniciada = false;

    try {
        $accion = $_POST["accion"];
        $idConocimiento = (int) ($_POST["id_conocimiento"] ?? 0);

        $titulo = trim($_POST["titulo"] ?? "");
        $contenido = trim($_POST["contenido"] ?? "");
        $palabrasClave = trim($_POST["palabras_clave"] ?? "");
        $nuevaCategoria = trim($_POST["nueva_categoria"] ?? "");
        $descripcionCategoria = trim($_POST["descripcion_categoria"] ?? "");
        $categoriaId = (int) ($_POST["categoria_id"] ?? 0);

        if ($accion === "modificar_conocimiento" && $idConocimiento <= 0) {
            throw new InvalidArgumentException(
                "El conocimiento que deseas modificar no es válido."
            );
        }

        if ($titulo === "" || $contenido === "") {
            throw new InvalidArgumentException(
                "El título y el contenido son obligatorios."
            );
        }

        if (mb_strlen($titulo, "UTF-8") > 255) {
            throw new InvalidArgumentException(
                "El título no puede superar los 255 caracteres."
            );
        }

        if (
            $nuevaCategoria !== "" &&
            mb_strlen($nuevaCategoria, "UTF-8") > 100
        ) {
            throw new InvalidArgumentException(
                "El nombre de la categoría no puede superar los 100 caracteres."
            );
        }

        $conexion_ia->begin_transaction();
        $transaccionIniciada = true;

        // VALIDAR QUE EXISTA EL CONOCIMIENTO SI SE ESTÁ EDITANDO
        if ($accion === "modificar_conocimiento") {
            $consulta = $conexion_ia->prepare(
                "SELECT id FROM conocimientos WHERE id = ? LIMIT 1"
            );
            $consulta->bind_param("i", $idConocimiento);
            $consulta->execute();

            $existe = $consulta->get_result()->fetch_assoc();
            $consulta->close();

            if (!$existe) {
                throw new InvalidArgumentException(
                    "No se encontró el conocimiento que deseas modificar."
                );
            }
        }

        // CREAR O REUTILIZAR CATEGORÍA
        if ($nuevaCategoria !== "") {
            $consulta = $conexion_ia->prepare(
                "SELECT id, activo FROM categorias
                 WHERE LOWER(nombre) = LOWER(?)
                 LIMIT 1"
            );
            $consulta->bind_param("s", $nuevaCategoria);
            $consulta->execute();

            $categoriaExistente = $consulta->get_result()->fetch_assoc();
            $consulta->close();

            if ($categoriaExistente) {
                $categoriaId = (int) $categoriaExistente["id"];

                // Reactivar la categoría si estaba inactiva
                if ((int) $categoriaExistente["activo"] !== 1) {
                    $consulta = $conexion_ia->prepare(
                        "UPDATE categorias SET activo = 1 WHERE id = ?"
                    );
                    $consulta->bind_param("i", $categoriaId);
                    $consulta->execute();
                    $consulta->close();
                }
            } else {
                $consulta = $conexion_ia->prepare(
                    "INSERT INTO categorias (nombre, descripcion, activo)
                     VALUES (?, ?, 1)"
                );
                $consulta->bind_param(
                    "ss",
                    $nuevaCategoria,
                    $descripcionCategoria
                );
                $consulta->execute();

                $categoriaId = $conexion_ia->insert_id;
                $consulta->close();
            }
        } else {
            if ($categoriaId <= 0) {
                throw new InvalidArgumentException(
                    "Selecciona una categoría o escribe una nueva."
                );
            }

            $consulta = $conexion_ia->prepare(
                "SELECT id FROM categorias WHERE id = ? LIMIT 1"
            );
            $consulta->bind_param("i", $categoriaId);
            $consulta->execute();

            $categoriaValida = $consulta->get_result()->fetch_assoc();
            $consulta->close();

            if (!$categoriaValida) {
                throw new InvalidArgumentException(
                    "La categoría seleccionada no es válida."
                );
            }
        }

        // INSERTAR O ACTUALIZAR CONOCIMIENTO
        if ($accion === "guardar_conocimiento") {
            $consulta = $conexion_ia->prepare(
                "INSERT INTO conocimientos
                 (categoria_id, titulo, contenido, palabras_clave, activo)
                 VALUES (?, ?, ?, ?, 1)"
            );

            $consulta->bind_param(
                "isss",
                $categoriaId,
                $titulo,
                $contenido,
                $palabrasClave
            );
            $consulta->execute();

            $idConocimiento = $conexion_ia->insert_id;
            $consulta->close();
        } else {
            $consulta = $conexion_ia->prepare(
                "UPDATE conocimientos
                 SET categoria_id = ?, titulo = ?, contenido = ?,
                     palabras_clave = ?
                 WHERE id = ?"
            );

            $consulta->bind_param(
                "isssi",
                $categoriaId,
                $titulo,
                $contenido,
                $palabrasClave,
                $idConocimiento
            );
            $consulta->execute();
            $consulta->close();

            // Reemplazar las preguntas anteriores por las nuevas
            $consulta = $conexion_ia->prepare(
                "DELETE FROM preguntas_frecuentes
                 WHERE conocimiento_id = ?"
            );
            $consulta->bind_param("i", $idConocimiento);
            $consulta->execute();
            $consulta->close();
        }

        // GUARDAR LAS PREGUNTAS FRECUENTES
        $textoPreguntas = trim($_POST["preguntas"] ?? "");

        $preguntas = preg_split(
            '/\R/u',
            $textoPreguntas,
            -1,
            PREG_SPLIT_NO_EMPTY
        );

        $preguntas = array_unique(
            array_filter(
                array_map("trim", $preguntas),
                function ($pregunta) {
                    return $pregunta !== "";
                }
            )
        );

        $consultaPregunta = $conexion_ia->prepare(
            "INSERT INTO preguntas_frecuentes
             (conocimiento_id, pregunta, activo)
             VALUES (?, ?, 1)"
        );

        foreach ($preguntas as $pregunta) {
            $consultaPregunta->bind_param(
                "is",
                $idConocimiento,
                $pregunta
            );
            $consultaPregunta->execute();
        }

        $consultaPregunta->close();

        $conexion_ia->commit();
        $transaccionIniciada = false;

        $_SESSION["mensaje_chatbot"] =
            $accion === "guardar_conocimiento"
                ? "El conocimiento se agregó correctamente."
                : "El conocimiento se modificó correctamente.";

    } catch (InvalidArgumentException $e) {
        if ($transaccionIniciada) {
            $conexion_ia->rollback();
        }

        $_SESSION["error_chatbot"] = $e->getMessage();

    } catch (Throwable $e) {
        if ($transaccionIniciada) {
            $conexion_ia->rollback();
        }

        error_log("Error al guardar conocimiento: " . $e->getMessage());

        $_SESSION["error_chatbot"] =
            "No se pudo guardar el conocimiento. Revisa los datos e inténtalo nuevamente.";
    }

    header("Location: panelAdmin.php?seccion=chatbot");
    exit();
}


// ACTIVAR O DESACTIVAR UN CONOCIMIENTO
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    ($_POST["accion"] ?? "") === "cambiar_estado_conocimiento"
) {
    try {
        $idConocimiento = (int) ($_POST["id_conocimiento"] ?? 0);

        if ($idConocimiento <= 0) {
            throw new InvalidArgumentException(
                "El conocimiento seleccionado no es válido."
            );
        }

        $consulta = $conexion_ia->prepare(
            "UPDATE conocimientos
             SET activo = CASE WHEN activo = 1 THEN 0 ELSE 1 END
             WHERE id = ?"
        );
        $consulta->bind_param("i", $idConocimiento);
        $consulta->execute();

        if ($consulta->affected_rows > 0) {
            $_SESSION["mensaje_chatbot"] =
                "El estado del conocimiento se cambió correctamente.";
        } else {
            $_SESSION["error_chatbot"] =
                "No se encontró el conocimiento o su estado no cambió.";
        }

        $consulta->close();

    } catch (Throwable $e) {
        error_log("Error al cambiar estado: " . $e->getMessage());
        $_SESSION["error_chatbot"] =
            "No se pudo cambiar el estado del conocimiento.";
    }

    header("Location: panelAdmin.php?seccion=chatbot");
    exit();
}


// ELIMINAR UN CONOCIMIENTO
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    ($_POST["accion"] ?? "") === "eliminar_conocimiento"
) {
    try {
        $idConocimiento = (int) ($_POST["id_conocimiento"] ?? 0);

        if ($idConocimiento <= 0) {
            throw new InvalidArgumentException(
                "El conocimiento seleccionado no es válido."
            );
        }

        $consulta = $conexion_ia->prepare(
            "DELETE FROM conocimientos WHERE id = ?"
        );
        $consulta->bind_param("i", $idConocimiento);
        $consulta->execute();

        if ($consulta->affected_rows > 0) {
            $_SESSION["mensaje_chatbot"] =
                "El conocimiento se eliminó correctamente.";
        } else {
            $_SESSION["error_chatbot"] =
                "No se encontró el conocimiento que intentas eliminar.";
        }

        $consulta->close();

    } catch (Throwable $e) {
        error_log("Error al eliminar conocimiento: " . $e->getMessage());
        $_SESSION["error_chatbot"] =
            "No se pudo eliminar el conocimiento.";
    }

    header("Location: panelAdmin.php?seccion=chatbot");
    exit();
}

// ELIMINAR ALUMNO
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    ($_POST["accion"] ?? "") === "eliminar"
) {
    $tablasPermitidas = [
        "promedio" => "alumnos_promedio",
        "prototipo" => "alumnos_prototipo",
        "experiencia" => "alumnos_experiencia"
    ];

    $seccionEliminar = $_POST["seccion"] ?? "";
    $idEliminar = filter_var(
        $_POST["id"] ?? null,
        FILTER_VALIDATE_INT
    );

    if (
        isset($tablasPermitidas[$seccionEliminar]) &&
        $idEliminar !== false &&
        $idEliminar !== null &&
        $idEliminar > 0
    ) {
        $tabla = $tablasPermitidas[$seccionEliminar];

        $stmt = $conexion->prepare(
            "DELETE FROM `$tabla` WHERE id = ?"
        );

        $stmt->bind_param("i", $idEliminar);
        $stmt->execute();
        $stmt->close();
    }

    header(
        "Location: panelAdmin.php?seccion=" .
        urlencode($seccionEliminar)
    );
    exit();
}

// AGREGAR ALUMNO

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $tipo = $_POST["tipo"] ?? "";

    $no_control = $_POST["no_control"] ?? "";
    $nombre = $_POST["nombre"] ?? "";
    $apellidos = $_POST["apellidos"] ?? "";
    $estado = $_POST["estado"] ?? "En proceso";

    // PROMEDIO

    if ($tipo == "promedio") {

        $promedio = $_POST["promedio"] ?? "";
        $doc_identidad = isset($_POST["doc_identidad"]) ? 1 : 0;
        $doc_escolares = isset($_POST["doc_escolares"]) ? 1 : 0;

        
        $sql = "INSERT INTO alumnos_promedio
                (no_control, nombre, apellidos, promedio, estado,
                doc_identidad, doc_escolares)
                VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conexion->prepare($sql);

        $stmt->bind_param(
            "sssssii",
            $no_control,
            $nombre,
            $apellidos,
            $promedio,
            $estado,
            $doc_identidad,
            $doc_escolares
        );

        $stmt->execute();

        $stmt->close();

        header("Location: panelAdmin.php?seccion=promedio");
        exit();
    }

    // PROTOTIPO / PROYECTO

    if ($tipo == "prototipo") {

        $proyecto = $_POST["proyecto"] ?? "";
        $doc_identidad = isset($_POST["doc_identidad"]) ? 1 : 0;
        $doc_escolares = isset($_POST["doc_escolares"]) ? 1 : 0;

        $sql = "INSERT INTO alumnos_prototipo
                (no_control, nombre, apellidos, proyecto, estado, doc_identidad, doc_escolares)
                VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conexion->prepare($sql);

        $stmt->bind_param(
            "sssssii",
            $no_control,
            $nombre,
            $apellidos,
            $proyecto,
            $estado,
            $doc_identidad,
            $doc_escolares
        );

        $stmt->execute();

        $stmt->close();

        header("Location: panelAdmin.php?seccion=prototipo");
        exit();
    }

    // EXPERIENCIA LABORAL

    if ($tipo == "experiencia") {

        $empresa = $_POST["empresa"] ?? "";
        $doc_identidad = isset($_POST["doc_identidad"]) ? 1 : 0;
        $doc_escolares = isset($_POST["doc_escolares"]) ? 1 : 0;

        $sql = "INSERT INTO alumnos_experiencia
                (no_control, nombre, apellidos, empresa, estado, doc_identidad, doc_escolares)
                VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conexion->prepare($sql);

        $stmt->bind_param(
            "sssssii",
            $no_control,
            $nombre,
            $apellidos,
            $empresa,
            $estado,
            $doc_identidad,
            $doc_escolares
        );

        $stmt->execute();

        $stmt->close();

        header("Location: panelAdmin.php?seccion=experiencia");
        exit();
    }

}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="style.css">

    <title>Panel de Administración | CETis 120</title>

    <link rel="icon" href="imagenes/icono.ico" type="image/x-icon">

</head>


<body>

<header>

    <div class="logo">

        <div class="logo-jaguar">

            <img
                src="imagenes/logo.png"
                alt="Logo del CETis 120"
            >

        </div>

        <div class="logo-text">

            <h2>CETis 120</h2>

            <p>
                Centro de Estudios Tecnológicos industrial y de servicios 120
            </p>

        </div>

    </div>

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

<section class="panel-admin">

    <h1>
        PANEL DE ADMINISTRACIÓN
    </h1>

    <p>
        Selecciona una base de datos para administrarla.
    </p>


    <!-- OPCIONES -->

    <div class="opciones-admin">


        <a href="panelAdmin.php?seccion=promedio">

            <h2>
                Titulación por Promedio
            </h2>

            <p>
                Administrar alumnos y documentos de titulación por Promedio.
            </p>

        </a>


        <a href="panelAdmin.php?seccion=prototipo">

            <h2>
                Prototipo / Proyecto
            </h2>

            <p>
                Administrar alumnos y documentos de titulación por Prototipo/Proyecto.
            </p>

        </a>


        <a href="panelAdmin.php?seccion=experiencia">

            <h2>
                Experiencia Laboral
            </h2>

            <p>
                Administrar alumnos y documentos de titulación por Experiencia Laboral.
            </p>

        </a>

        <a href="panelAdmin.php?seccion=chatbot">
            <h2>Alimentar chatbot</h2>
            <p>
                Agregar conocimientos, respuestas y preguntas frecuentes
                para el asistente virtual.
            </p>
        </a>

    </div>


    <!-- PROMEDIO -->

    <?php if ($seccion == 'promedio'): ?>

        <section class="tabla-admin">

        <br>

            <h2>
                Alumnos - Promedio
            </h2>

            <div class="formulario-admin">

    <div class="formulario-titulo">
        <h3>Agregar alumno</h3>
        <p>Registra los datos del alumno para iniciar su proceso de titulación.</p>
    </div>

    <form method="POST">

        <input type="hidden" name="tipo" value="promedio">

        <div class="form-grid">

            <div class="campo">
                <label>No. de Control</label>
                <input
                    type="text"
                    name="no_control"
                    placeholder="Ej. 20260001"
                    required
                >
            </div>

            <div class="campo">
                <label>Nombre</label>
                <input
                    type="text"
                    name="nombre"
                    placeholder="Nombre del alumno"
                    required
                >
            </div>

            <div class="campo campo-completo">
                <label>Apellidos</label>
                <input
                    type="text"
                    name="apellidos"
                    placeholder="Apellidos del alumno"
                    required
                >
            </div>

            <div class="campo">
                <label>Promedio</label>
                <input
                    type="number"
                    name="promedio"
                    min="0"
                    max="10"
                    step="0.1"
                    placeholder="Ej. 8.7"
                    required
                >
            </div>

            <div class="campo">
                <label>Documentos de identidad</label>
            <div class="checkbox-documentos">
                <input
                type="checkbox"
                name="doc_identidad"
                value="1"
                >
            <span>Entregados</span>
            </div>
        </div>

            <div class="campo">
                <label>Documentos escolares</label>

                <div class="checkbox-documentos">
                    <input
                        type="checkbox"
                        name="doc_escolares"
                        value="1"
                    >
                    <span>Entregados</span>
                </div>
            </div>

            <div class="campo">
                <label>Estado</label>

                <select name="estado">

                    <option value="En proceso">
                        En proceso
                    </option>

                    <option value="Terminado">
                        Terminado
                    </option>

                </select>
            </div>

            </div>

            <div class="formulario-boton">

                <button type="submit">
                     + Guardar alumno
                </button>
            </div>

    </form>

</div>

            <div class="contenedor-tabla">
                
            <table>

                <thead>
                    <tr>
                        <th>No. de Control</th>
                        <th>Nombre</th>
                        <th>Apellidos</th>
                        <th>Promedio</th>
                        <th>Documentos de identidad</th>
                        <th>Documentos escolares</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>

                <tbody>

                    <?php

                    $resultado = $conexion->query(
                        "SELECT * FROM alumnos_promedio ORDER BY id DESC"
                    );

                    while ($alumno = $resultado->fetch_assoc()):

                    ?>
                    
                        <tr>

                            <td>
                                <?= htmlspecialchars($alumno["no_control"]) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($alumno["nombre"]) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($alumno["apellidos"]) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($alumno["promedio"]) ?>
                            </td>

                            <td>
                                <?php if ($alumno["doc_identidad"] == 1): ?>

                                    <span class="doc_identidad-si">
                                        ✓ Entregados
                                    </span>

                                <?php else: ?>

                                    <span class="doc_identidad-no">
                                        ✗ Pendientes
                                    </span>

                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if ($alumno["doc_escolares"] == 1): ?>

                                    <span class="doc_identidad-si">
                                        ✓ Entregados
                                    </span>

                                <?php else: ?>

                                    <span class="doc_identidad-no">
                                        ✗ Pendientes
                                    </span>

                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if ($alumno["estado"] == "Terminado"): ?>
                                
                                    <span class="estado estado-terminado">
                                        Terminado
                                    </span>
                                
                                <?php else: ?>
                                
                                    <span class="estado estado-proceso">
                                        <?= htmlspecialchars($alumno["estado"]) ?>
                                    </span>
                                
                                <?php endif; ?>

                            </td>

                            <td>
                                <form method="POST"
                                    onsubmit="return confirm('¿Seguro que deseas eliminar este alumno? Esta acción no se puede deshacer.');">

                                    <input type="hidden" name="accion" value="eliminar">

                                    <input
                                        type="hidden"
                                        name="seccion"
                                        value="<?= htmlspecialchars($seccion, ENT_QUOTES, 'UTF-8') ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?= (int)$alumno['id'] ?>"
                                    >

                                    <button type="submit" class="boton-eliminar">
                                        Eliminar
                                    </button>
                                </form>
                            </td>

                        </tr>

                    <?php endwhile; ?>

                </tbody>

            </table>

            </div>

        </section>


    <!-- PROTOTIPO -->

    <?php elseif ($seccion == 'prototipo'): ?>

        <section class="tabla-admin">

        <br>

            <h2>
                Alumnos - Prototipo / Proyecto
            </h2>

            <div class="formulario-admin">

    <div class="formulario-titulo">
        <h3>Agregar alumno</h3>
        <p>Registra los datos del alumno para iniciar su proceso de titulación</p>
    </div>

    <form method="POST">

        <input type="hidden" name="tipo" value="prototipo">

        <div class="form-grid">

            <div class="campo">
                <label>No. Control</label>
                <input
                    type="text"
                    name="no_control"
                    placeholder="Ej. 20260001"
                    required
                >
            </div>


             <div class="campo">
                <label>Nombre</label>
                <input
                    type="text"
                    name="nombre"
                    placeholder="Nombre del alumno"
                    required
                >
            </div>


            <div class="campo campo-completo">
                <label>Apellidos</label>
                <input
                    type="text"
                    name="apellidos"
                    placeholder="Apellidos del alumno"
                    required
                >
            </div>


            <div class="campo">
                <label>Prototipo</label>
                <input
                    type="text"
                    name="proyecto"
                    placeholder="Descripción del prototipo"
                    required
                >
            </div>

            <div class="campo">
                <label>Documentos de identidad</label>
            <div class="checkbox-documentos">
                <input
                type="checkbox"
                name="doc_identidad"
                value="1"
                >
            <span>Entregados</span>
            </div>
    </div>

            <div class="campo">
                <label>Documentos escolares</label>

                <div class="checkbox-documentos">
                    <input
                        type="checkbox"
                        name="doc_escolares"
                        value="1"
                    >
                    <span>Entregados</span>
                </div>
            </div>

            <div class="campo">
                <label>Estado</label>

                <select name="estado">

                    <option value="En proceso">
                        En proceso
                    </option>

                    <option value="Terminado">
                        Terminado
                    </option>

                </select>

            </div>

        </div>

        <div class="formulario-boton">

             <button type="submit">
                + Guardar alumno
            </button>
        </div>

            </form>
</div>

            <div class="contenedor-tabla">
                
            <table>

                <thead>
                    <tr>
                        <th>No. Control</th>
                        <th>Nombre</th>
                        <th>Apellidos</th>
                        <th>Proyecto</th>
                        <th>Documentos de identidad</th>
                        <th>Documentos escolares</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>

                <tbody>

                    <?php

                    $resultado = $conexion->query(
                        "SELECT * FROM alumnos_prototipo ORDER BY id DESC"
                    );

                    while ($alumno = $resultado->fetch_assoc()):

                    ?>

                        <tr>

                            <td>
                                <?= htmlspecialchars($alumno["no_control"]) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($alumno["nombre"]) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($alumno["apellidos"]) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($alumno["proyecto"]) ?>
                            </td>

                            <td>
                                <?php if ($alumno["doc_identidad"] == 1): ?>

                                    <span class="doc_identidad-si">
                                        ✓ Entregados
                                    </span>

                                <?php else: ?>

                                    <span class="doc_identidad-no">
                                        ✗ Pendientes
                                    </span>

                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if ($alumno["doc_escolares"] == 1): ?>

                                    <span class="doc_identidad-si">
                                        ✓ Entregados
                                    </span>

                                <?php else: ?>

                                    <span class="doc_identidad-no">
                                        ✗ Pendientes
                                    </span>

                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if ($alumno["estado"] == "Terminado"): ?>
                                
                                    <span class="estado estado-terminado">
                                        Terminado
                                    </span>
                                
                                <?php else: ?>
                                
                                    <span class="estado estado-proceso">
                                        <?= htmlspecialchars($alumno["estado"]) ?>
                                    </span>
                                
                                <?php endif; ?>

                            </td>

                            <td>
                                <form method="POST"
                                    onsubmit="return confirm('¿Seguro que deseas eliminar este alumno? Esta acción no se puede deshacer.');">

                                    <input type="hidden" name="accion" value="eliminar">

                                    <input
                                        type="hidden"
                                        name="seccion"
                                        value="<?= htmlspecialchars($seccion, ENT_QUOTES, 'UTF-8') ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?= (int)$alumno['id'] ?>"
                                    >

                                    <button type="submit" class="boton-eliminar">
                                        Eliminar
                                    </button>
                                </form>
                            </td>

                        </tr>

                    <?php endwhile; ?>

                </tbody>

            </table>

            </div>

        </section>


    <!-- ==========================================
         EXPERIENCIA
    =========================================== -->

    <?php elseif ($seccion == 'experiencia'): ?>

        <section class="tabla-admin">

        <br>

            <h2>
                Alumnos - Experiencia Laboral
            </h2>

            <div class="formulario-admin">

    <div class="formulario-titulo">   
            <h3>Agregar alumno</h3>
            <p>Registra los datos del alumno para iniciar su proceso de titulación.</p>
    </div>

     <form method="POST">

        <input type="hidden" name="tipo" value="experiencia">

        <div class="form-grid">

            <div class="campo">
                <label>No. Control</label>
                <input
                    type="text"
                    name="no_control"
                    placeholder="Ej. 20260001"
                    required
                >
            </div>

            <div class="campo">
                <label>Nombre</label>
                <input
                    type="text"
                    name="nombre"
                    placeholder="Nombre del alumno"
                    required
                >
            </div>

            <div class="campo campo-completo">
                <label>Apellidos</label>
                <input
                    type="text"
                    name="apellidos"
                    placeholder="Apellidos del alumno"
                    required
                >
            </div>

            <div class="campo">
                <label>Empresa</label>
                <input
                    type="text"
                    name="empresa"
                    placeholder="Empresa donde trabajó"
                    required
                >
            </div>

            <div class="campo">
                <label>Documentos de identidad</label>
            <div class="checkbox-documentos">
                <input
                type="checkbox"
                name="doc_identidad"
                value="1"
                >

            <span>Entregados</span>
            </div>
        </div>

            <div class="campo">
                <label>Documentos escolares</label>

                <div class="checkbox-documentos">
                    <input
                        type="checkbox"
                        name="doc_escolares"
                        value="1"
                    >
                    <span>Entregados</span>
                </div>
            </div>

            <div class="campo">
                <label>Estado</label>

                <select name="estado">

                    <option value="En proceso">
                        En proceso
                    </option>

                    <option value="Terminado">
                        Terminado
                    </option>

                </select>

            </div>

        </div>

            <div class="formulario-boton">

                <button type="submit">
                   + Guardar alumno
                </button>
            </div>

                </form>
    
</div>

        <div class="contenedor-tabla">
            
        <table>

            <thead>
                <tr>
                    <th>No. Control</th>
                    <th>Nombre</th>
                    <th>Apellidos</th>
                    <th>Empresa</th>
                    <th>Documentos de identidad</th>
                    <th>Documentos escolares</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>

            <tbody>

                <?php

                $resultado = $conexion->query(
                    "SELECT * FROM alumnos_experiencia ORDER BY id DESC"
                );

                while ($alumno = $resultado->fetch_assoc()):

                ?>

                    <tr>

                        <td>
                            <?= htmlspecialchars($alumno["no_control"]) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($alumno["nombre"]) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($alumno["apellidos"]) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($alumno["empresa"]) ?>
                        </td>

                        <td>
                            <?php if ($alumno["doc_identidad"] == 1): ?>

                                <span class="doc_identidad-si">
                                    ✓ Entregados
                                </span>

                            <?php else: ?>

                                <span class="doc_identidad-no">
                                    ✗ Pendientes
                                </span>

                            <?php endif; ?>
                        </td>

                        <td>
                            <?php if ($alumno["doc_escolares"] == 1): ?>

                                <span class="doc_identidad-si">
                                    ✓ Entregados
                                </span>

                            <?php else: ?>

                                <span class="doc_identidad-no">
                                    ✗ Pendientes
                                </span>

                            <?php endif; ?>
                        </td>

                            <td>
                            <?php if ($alumno["estado"] == "Terminado"): ?>
                            
                                <span class="estado estado-terminado">
                                    Terminado
                                </span>
                            
                            <?php else: ?>
                            
                                <span class="estado estado-proceso">
                                    <?= htmlspecialchars($alumno["estado"]) ?>
                                </span>
                            
                            <?php endif; ?>

                        </td>

                        <td>
                            <form method="POST"
                                onsubmit="return confirm('¿Seguro que deseas eliminar este alumno? Esta acción no se puede deshacer.');">

                                <input type="hidden" name="accion" value="eliminar">

                                <input
                                    type="hidden"
                                    name="seccion"
                                    value="<?= htmlspecialchars($seccion, ENT_QUOTES, 'UTF-8') ?>"
                                >

                                <input
                                    type="hidden"
                                    name="id"
                                    value="<?= (int)$alumno['id'] ?>"
                                >

                                <button type="submit" class="boton-eliminar">
                                    Eliminar
                                </button>
                            </form>
                        </td>

                    </tr>

                <?php endwhile; ?>

            </tbody>

        </table>

            </div>

    </section>

        
    
    <?php elseif ($seccion == 'chatbot'): ?>

        <?php
        // Conocimiento a modificar
        $idEditar = (int) ($_GET["editar"] ?? 0);
        $conocimientoEditar = null;
        $preguntasEdicion = "";

        if ($idEditar > 0) {
            $consulta = $conexion_ia->prepare(
                "SELECT * FROM conocimientos WHERE id = ? LIMIT 1"
            );
            $consulta->bind_param("i", $idEditar);
            $consulta->execute();

            $conocimientoEditar = $consulta->get_result()->fetch_assoc();
            $consulta->close();

            if ($conocimientoEditar) {
                $consulta = $conexion_ia->prepare(
                    "SELECT pregunta FROM preguntas_frecuentes
                     WHERE conocimiento_id = ?
                     ORDER BY id ASC"
                );
                $consulta->bind_param("i", $idEditar);
                $consulta->execute();

                $resultadoPreguntas = $consulta->get_result();
                $listaPreguntas = [];

                while ($filaPregunta = $resultadoPreguntas->fetch_assoc()) {
                    $listaPreguntas[] = $filaPregunta["pregunta"];
                }

                $preguntasEdicion = implode("\n", $listaPreguntas);
                $consulta->close();
            }
        }

        // Categorías disponibles
        $categorias = $conexion_ia->query(
            "SELECT id, nombre, activo
             FROM categorias
             ORDER BY nombre ASC"
        );

        // Conocimientos registrados
        $conocimientos = $conexion_ia->query(
            "SELECT
                c.id,
                c.categoria_id,
                c.titulo,
                c.contenido,
                c.palabras_clave,
                c.activo,
                cat.nombre AS categoria,
                (
                    SELECT COUNT(*)
                    FROM preguntas_frecuentes pf
                    WHERE pf.conocimiento_id = c.id
                ) AS total_preguntas
             FROM conocimientos c
             INNER JOIN categorias cat ON cat.id = c.categoria_id
             ORDER BY c.id DESC"
        );

        $mensajeChatbot = $_SESSION["mensaje_chatbot"] ?? "";
        $errorChatbot = $_SESSION["error_chatbot"] ?? "";

        unset($_SESSION["mensaje_chatbot"]);
        unset($_SESSION["error_chatbot"]);
        ?>

        <section class="tabla-admin">

            <br>
            <h2>Alimentar chatbot</h2>

            <?php if ($mensajeChatbot !== ""): ?>
                <p><?= htmlspecialchars($mensajeChatbot, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>

            <?php if ($errorChatbot !== ""): ?>
                <p><?= htmlspecialchars($errorChatbot, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>


            <!-- FORMULARIO PARA AGREGAR O MODIFICAR -->

            <div class="formulario-admin">

                <div class="formulario-titulo">
                    <h3>
                        <?= $conocimientoEditar
                            ? "Modificar conocimiento"
                            : "Agregar conocimiento" ?>
                    </h3>

                    <p>
                        Registra o actualiza la información que utilizará
                        el chatbot para responder.
                    </p>
                </div>

                <form method="POST">

                    <input
                        type="hidden"
                        name="accion"
                        value="<?= $conocimientoEditar
                            ? 'modificar_conocimiento'
                            : 'guardar_conocimiento' ?>"
                    >

                    <?php if ($conocimientoEditar): ?>
                        <input
                            type="hidden"
                            name="id_conocimiento"
                            value="<?= (int)$conocimientoEditar['id'] ?>"
                        >
                    <?php endif; ?>

                    <div class="form-grid">

                        <div class="campo campo-completo">
                            <label for="categoria_id">Categoría existente</label>

                            <select name="categoria_id" id="categoria_id">
                                <option value="">Selecciona una categoría</option>

                                <?php while ($categoria = $categorias->fetch_assoc()): ?>
                                    <option
                                        value="<?= (int)$categoria['id'] ?>"
                                        <?= $conocimientoEditar &&
                                            (int)$conocimientoEditar['categoria_id'] ===
                                            (int)$categoria['id']
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        <?= htmlspecialchars(
                                            $categoria['nombre'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                        <?= (int)$categoria['activo'] === 0
                                            ? ' (Inactiva)'
                                            : '' ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="campo">
                            <label for="nueva_categoria">Nueva categoría (opcional)</label>
                            <input
                                type="text"
                                name="nueva_categoria"
                                id="nueva_categoria"
                                maxlength="100"
                                placeholder="Ej. Servicio social"
                            >
                        </div>

                        <div class="campo">
                            <label for="descripcion_categoria">
                                Descripción de la categoría (opcional)
                            </label>
                            <input
                                type="text"
                                name="descripcion_categoria"
                                id="descripcion_categoria"
                                placeholder="Descripción breve"
                            >
                        </div>

                        <div class="campo campo-completo">
                            <label for="titulo">Título del conocimiento</label>
                            <input
                                type="text"
                                name="titulo"
                                id="titulo"
                                maxlength="255"
                                required
                                value="<?= htmlspecialchars(
                                    $conocimientoEditar['titulo'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                placeholder="Ej. ¿En qué consiste la titulación por promedio?"
                            >
                        </div>

                        <div class="campo campo-completo">
                            <label for="contenido">Respuesta o contenido</label>
                            <textarea
                                name="contenido"
                                id="contenido"
                                rows="7"
                                required
                                placeholder="Escribe la respuesta completa..."
                            ><?= htmlspecialchars(
                                $conocimientoEditar['contenido'] ?? '',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?></textarea>
                        </div>

                        <div class="campo campo-completo">
                            <label for="palabras_clave">Palabras clave (separadas por coma)</label>
                            <input
                                type="text"
                                name="palabras_clave"
                                id="palabras_clave"
                                value="<?= htmlspecialchars(
                                    $conocimientoEditar['palabras_clave'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                placeholder="promedio, titulación, requisitos"
                            >
                        </div>

                        <div class="campo campo-completo">
                            <label for="preguntas">Preguntas frecuentes</label>
                            <textarea
                                name="preguntas"
                                id="preguntas"
                                rows="5"
                                placeholder="Escribe una pregunta por línea"
                            ><?= htmlspecialchars(
                                $preguntasEdicion,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?></textarea>

                            <small>
                                Escribe una pregunta por línea. Al modificar,
                                esta lista sustituirá las preguntas anteriores.
                            </small>
                        </div>

                    </div>

                    <div class="formulario-boton">
                        <button type="submit">
                            <?= $conocimientoEditar
                                ? "Guardar cambios"
                                : "+ Guardar conocimiento" ?>
                        </button>
                    </div>

                    <?php if ($conocimientoEditar): ?>
                        <p>
                            <a href="panelAdmin.php?seccion=chatbot">
                                Cancelar modificación
                            </a>
                        </p>
                    <?php endif; ?>

                </form>
            </div>


            <!-- TABLA DE CONOCIMIENTOS -->

            <div class="tabla-admin">

                <h3>Conocimientos registrados</h3>

                <div class="contenedor-tabla">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Categoría</th>
                                <th>Título</th>
                                <th>Contenido</th>
                                <th>Palabras clave</th>
                                <th>Preguntas</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if ($conocimientos->num_rows > 0): ?>

                                <?php while ($conocimiento = $conocimientos->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= (int)$conocimiento['id'] ?></td>

                                        <td>
                                            <?= htmlspecialchars(
                                                $conocimiento['categoria'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars(
                                                $conocimiento['titulo'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </td>

                                        <td>
                                            <details>
                                                <summary>Ver contenido</summary>
                                                <p>
                                                    <?= nl2br(htmlspecialchars(
                                                        $conocimiento['contenido'],
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    )) ?>
                                                </p>
                                            </details>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars(
                                                $conocimiento['palabras_clave'] ?? '',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </td>

                                        <td>
                                            <?= (int)$conocimiento['total_preguntas'] ?>
                                        </td>

                                        <td>
                                            <?php if ((int)$conocimiento['activo'] === 1): ?>
                                                <span class="estado estado-terminado">
                                                    Activo
                                                </span>
                                            <?php else: ?>
                                                <span class="estado estado-proceso">
                                                    Inactivo
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <p>
                                                <a href="panelAdmin.php?seccion=chatbot&amp;editar=<?= (int)$conocimiento['id'] ?>" style="display:inline-block; padding:8px 12px; margin-bottom:8px; background:#2563eb; color:white; text-decoration:none; border-radius:6px; font-size:13px;">
                                                    Modificar
                                                </a>
                                            </p>

                                            <form method="POST">
                                                <input
                                                    type="hidden"
                                                    name="accion"
                                                    value="cambiar_estado_conocimiento"
                                                >
                                                <input
                                                    type="hidden"
                                                    name="id_conocimiento"
                                                    value="<?= (int)$conocimiento['id'] ?>"
                                                >

                                                <button type="submit" style="padding:8px 12px; margin-bottom:8px; background:<?= (int)$conocimiento['activo'] === 1 ? '#d97706' : '#16a34a' ?>; color:white; border:none; border-radius:6px; cursor:pointer; font-size:13px;">
                                                    <?= (int)$conocimiento['activo'] === 1
                                                        ? '⏸ Desactivar'
                                                        : '▶ Activar' ?>
                                                </button>
                                            </form>

                                            <br>

                                            <form
                                                method="POST"
                                                onsubmit="return confirm('¿Seguro que deseas eliminar este conocimiento y sus preguntas asociadas? Esta acción no se puede deshacer.');"
                                            >
                                                <input
                                                    type="hidden"
                                                    name="accion"
                                                    value="eliminar_conocimiento"
                                                >
                                                <input
                                                    type="hidden"
                                                    name="id_conocimiento"
                                                    value="<?= (int)$conocimiento['id'] ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    class="boton-eliminar"
                                                >
                                                    Eliminar
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>

                            <?php else: ?>
                                <tr>
                                    <td colspan="8">
                                        No hay conocimientos registrados.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </section>

    <?php endif; ?>

    <br>

    <a href="logout.php">
        Cerrar sesión
    </a>

</section>

</main>


<footer>

    <p>
        © 2026 SIGEA &nbsp;·&nbsp; Proceso de Titulación
    </p>

    <p class="autores">
        Elaborado por: Miguel Angel Barcenas Gonzales - Cinthya Jaquelinne Castro Ramirez - Irving Gamiño Mejia - Kimberly Paola Lara Andrade  
    </p>

</footer>


</body>

</html>