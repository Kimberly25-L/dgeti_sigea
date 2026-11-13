<?php
session_start();
if (empty($_SESSION["cetis120_admin"])) {
    header("Location: presetamosmateriales.html?admin=login");
    exit;
}
require_once "conexion.php";

/* BASE DE CONOCIMIENTOS DEL ASISTENTE: se guarda en un archivo independiente */
$archivoConocimientos = __DIR__ . "/conocimientos_ia.json";
if (!file_exists($archivoConocimientos)) {
    file_put_contents($archivoConocimientos, "[]", LOCK_EX);
}
$leerConocimientos = static function () use ($archivoConocimientos) {
    $contenido = @file_get_contents($archivoConocimientos);
    $datos = json_decode($contenido === false ? "[]" : $contenido, true);
    return is_array($datos) ? $datos : [];
};
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["accion_ia"])) {
    $conocimientos = $leerConocimientos();
    $accion = (string)$_POST["accion_ia"];
    $id = trim((string)($_POST["id_ia"] ?? ""));

    if ($accion === "eliminar" && $id !== "") {
        $conocimientos = array_values(array_filter($conocimientos, static function ($item) use ($id) {
            return (string)($item["id"] ?? "") !== $id;
        }));
        file_put_contents($archivoConocimientos, json_encode($conocimientos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
        header("Location: panel-admin.php?ia=eliminado");
        exit;
    }

    if ($accion === "guardar") {
        $tema = trim((string)($_POST["tema_ia"] ?? ""));
        $palabras = trim((string)($_POST["palabras_ia"] ?? ""));
        $respuesta = trim((string)($_POST["respuesta_ia"] ?? ""));
        if ($tema !== "" && $palabras !== "" && $respuesta !== "") {
            $registro = [
                "id" => $id !== "" ? $id : bin2hex(random_bytes(8)),
                "tema" => mb_substr($tema, 0, 120),
                "palabras_clave" => array_values(array_filter(array_map("trim", explode(",", mb_strtolower($palabras, "UTF-8"))), static fn($v) => $v !== "")),
                "respuesta" => mb_substr($respuesta, 0, 2000)
            ];
            $encontrado = false;
            foreach ($conocimientos as $indice => $item) {
                if ((string)($item["id"] ?? "") === $registro["id"]) {
                    $conocimientos[$indice] = $registro;
                    $encontrado = true;
                    break;
                }
            }
            if (!$encontrado) $conocimientos[] = $registro;
            file_put_contents($archivoConocimientos, json_encode(array_values($conocimientos), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
            header("Location: panel-admin.php?ia=guardado");
            exit;
        }
        header("Location: panel-admin.php?ia=error");
        exit;
    }
}
$conocimientosIA = $leerConocimientos();
$editarIA = null;
if (isset($_GET["editar_ia"])) {
    foreach ($conocimientosIA as $item) {
        if ((string)($item["id"] ?? "") === (string)$_GET["editar_ia"]) { $editarIA = $item; break; }
    }
}

/* ESCAPAR LOS DATOS PARA MOSTRARLOS DE FORMA SEGURA */
function h($valor) {
    return htmlspecialchars(
        (string)$valor,
        ENT_QUOTES,
        "UTF-8"
    );
}

/* CONSULTAR LAS SOLICITUDES GUARDADAS EN MYSQL */
try {
    $consulta = $pdo->query("
        SELECT
            folio,
            nombre_alumno,
            numero_control,
            especialidad,
            turno,
            materiales,
            fecha_solicitud,
            estado
        FROM solicitudes
        ORDER BY fecha_solicitud DESC, folio DESC
    ");

    $solicitudes = $consulta->fetchAll();

} catch (PDOException $e) {
    error_log($e->getMessage());
    exit("Error: no fue posible consultar las solicitudes. Revisa la conexión y la tabla solicitudes.");
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Panel de Administrador - CETis 120</title>

    <!-- CSS ORIGINAL -->
    <link rel="stylesheet" href="style2.css">

    <!-- AJUSTES VISUALES DEL PANEL -->
    <style>
        .admin-contenido {
            padding: 35px 20px 60px;
            min-height: 60vh;
        }

        .admin-titulo {
            text-align: center;
            color: white;
            margin: 10px 0 15px;
        }

        .admin-resumen {
            text-align: center;
            color: white;
            font-size: 18px;
            margin-bottom: 25px;
        }

        .admin-tarjeta {
            width: 95%;
            max-width: 1400px;
            box-sizing: border-box;
            margin: 0 auto;
            padding: 25px;
            background-color: white;
            color: #263449;
            border-radius: 14px;
            box-shadow: 0 5px 16px #0003;
        }

        .admin-tarjeta h2 {
            color: #741d35;
            text-align: center;
            margin-top: 0;
        }

        .admin-descripcion {
            text-align: center;
            margin-bottom: 25px;
        }

        .tabla-contenedor {
            width: 100%;
            overflow-x: auto;
            margin-top: 20px;
        }

        .tabla-admin {
            width: 100%;
            min-width: 950px;
            border-collapse: collapse;
            background-color: white;
            color: #222;
        }

        .tabla-admin th {
            background-color: #7b1e3a;
            color: white;
            padding: 14px 12px;
            text-align: center;
            border: 1px solid #5e162d;
            font-size: 15px;
        }

        .tabla-admin td {
            padding: 13px 12px;
            border: 1px solid #ccc;
            text-align: center;
            color: #222;
            background-color: white;
            font-size: 14px;
            overflow-wrap: anywhere;
        }

        .tabla-admin tbody tr:nth-child(even) td {
            background-color: #f2f2f2;
        }

        .tabla-admin tbody tr:hover td {
            background-color: #e8e8e8;
        }

        .estado-pendiente {
            display: inline-block;
            padding: 7px 11px;
            background-color: #fff3cd;
            color: #664d03;
            border-radius: 6px;
            font-weight: bold;
        }

        .estado-devuelto {
            display: inline-block;
            padding: 7px 11px;
            background-color: #d1e7dd;
            color: #0f5132;
            border-radius: 6px;
            font-weight: bold;
        }

        .estado-prestado {
            display: inline-block;
            padding: 7px 11px;
            background-color: #cfe2ff;
            color: #084298;
            border-radius: 6px;
            font-weight: bold;
        }

        .boton-admin {
            border: 0;
            border-radius: 6px;
            padding: 8px 10px;
            margin: 3px;
            cursor: pointer;
            font-weight: bold;
        }

        .boton-estado { background: #0d6efd; color: white; }
        .boton-eliminar { background: #b02a37; color: white; }

        .mensaje-panel {
            margin: 12px auto;
            text-align: center;
            font-weight: bold;
            color: #741d35;
        }

        /* Estilos exclusivos del apartado de alimentación de la IA */
        .ia-formulario { display: grid; gap: 10px; margin: 20px 0; padding: 20px; background: #fbf6f7; border: 1px solid #ead5dc; border-radius: 10px; }
        .ia-formulario label { font-weight: bold; color: #741d35; }
        .ia-formulario input, .ia-formulario textarea { width: 100%; box-sizing: border-box; padding: 11px 12px; border: 1px solid #c9aab4; border-radius: 7px; font: inherit; background: #fff; color: #263449; transition: border-color .2s, box-shadow .2s; }
        .ia-formulario input:focus, .ia-formulario textarea:focus { outline: none; border-color: #7b1e3a; box-shadow: 0 0 0 3px #7b1e3a20; }
        .ia-formulario textarea { min-height: 100px; resize: vertical; }
        .ia-formulario .ia-boton-guardar { background: #7b1e3a; color: white; border: 0; border-radius: 7px; padding: 11px 16px; cursor: pointer; font-weight: bold; transition: background .2s, transform .2s; }
        .ia-formulario .ia-boton-guardar:hover { background: #a00030; transform: translateY(-1px); }
        .ia-lista { display: grid; gap: 12px; margin-top: 20px; }
        .ia-item { padding: 16px; background: #fffafb; border: 1px solid #ead5dc; border-left: 4px solid #7b1e3a; border-radius: 8px; overflow-wrap: anywhere; }
        .ia-item h3 { margin: 0 0 8px; color: #741d35; }
        .ia-item p { margin: 6px 0; line-height: 1.5; }
        .ia-item a { display: inline-block; margin: 5px 8px 0 0; color: #741d35; font-weight: bold; }
        .ia-item a:hover { color: #a00030; }
        .ia-item button { background: #7b1e3a; color: white; border: 0; border-radius: 6px; padding: 8px 11px; cursor: pointer; font-weight: bold; }
        .ia-item button:hover { background: #a00030; }
        .ia-cancelar { color: #741d35; font-weight: bold; text-decoration: none; }
        .ia-cancelar:hover { text-decoration: underline; }
        @media (max-width: 600px) {
            .ia-formulario { padding: 14px; }
        }

        .sin-solicitudes {
            padding: 25px !important;
            text-align: center;
            color: #444 !important;
        }

        .admin-acciones {
            text-align: center;
            margin-top: 25px;
        }

        .admin-acciones a {
            display: inline-block;
            padding: 12px 20px;
            background-color: #800020;
            color: white;
            text-decoration: none;
            border-radius: 7px;
            font-weight: bold;
        }

        .admin-acciones a:hover {
            background-color: #a00030;
        }

        @media (max-width: 600px) {
            .admin-contenido {
                padding: 20px 8px 40px;
            }

            .admin-tarjeta {
                width: 100%;
                padding: 12px;
            }
        }
    </style>
</head>

<body>

    <!-- ENCABEZADO -->
    <header class="encabezado">

        <img
            src="logocetis120.png"
            alt="Logo CETis 120"
            class="logo"
        >

        <div>
            <h1>Sistema de Préstamos</h1>
            <h2>Panel de Administrador</h2>
            <p>CETis 120 - Josefa Ortiz de Domínguez</p>
        </div>

    </header>

    <!-- NAVEGACIÓN -->
    <nav class="menu">
        <a href="presetamosmateriales.html">Inicio</a>
        <a href="presetamosmateriales.html#registro">Registro</a>
    </nav>

    <!-- CONTENIDO DEL PANEL -->
    <main class="admin-contenido">

        <h1 class="admin-titulo">
            Panel de administrador
        </h1>

        <p class="admin-resumen">
            Total de solicitudes:
            <strong><?php echo count($solicitudes); ?></strong>
        </p>

        <section class="admin-tarjeta">

            <hr style="margin: 30px 0;">
            <h2>Solicitudes de préstamo</h2>

            <?php if (!empty($_GET["mensaje"])): ?>
                <p class="mensaje-panel"><?php echo h($_GET["mensaje"]); ?></p>
            <?php endif; ?>

            <h2>Registro de solicitudes</h2>

            <p class="admin-descripcion">
                Consulta las solicitudes de materiales escolares.
            </p>

            <p class="admin-descripcion">
                Solicitudes registradas:
                <strong><?php echo count($solicitudes); ?></strong>
            </p>

            <div class="tabla-contenedor">

                <table class="tabla-admin">

                    <thead>
                        <tr>
                            <th>Folio</th>
                            <th>Nombre del alumno</th>
                            <th>No. de control</th>
                            <th>Especialidad</th>
                            <th>Turno</th>
                            <th>Material</th>
                            <th>Fecha de solicitud</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if (count($solicitudes) > 0): ?>

                            <?php foreach ($solicitudes as $solicitud): ?>

                                <tr>
                                    <td>
                                        <?php echo h($solicitud["folio"]); ?>
                                    </td>

                                    <td>
                                        <?php echo h($solicitud["nombre_alumno"]); ?>
                                    </td>

                                    <td>
                                        <?php echo h($solicitud["numero_control"]); ?>
                                    </td>

                                    <td>
                                        <?php echo h($solicitud["especialidad"]); ?>
                                    </td>

                                    <td>
                                        <?php echo h($solicitud["turno"]); ?>
                                    </td>

                                    <td>
                                        <?php echo h($solicitud["materiales"]); ?>
                                    </td>

                                    <td>
                                        <?php echo h($solicitud["fecha_solicitud"]); ?>
                                    </td>

                                    <td>
                                        <?php
                                            $claseEstado = "estado-pendiente";
                                            if (strcasecmp($solicitud["estado"], "Devuelto") === 0) {
                                                $claseEstado = "estado-devuelto";
                                            } elseif (strcasecmp($solicitud["estado"], "Prestado") === 0) {
                                                $claseEstado = "estado-prestado";
                                            }
                                        ?>
                                        <span class="<?php echo $claseEstado; ?>">
                                            <?php echo h($solicitud["estado"]); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (strcasecmp($solicitud["estado"], "Pendiente") === 0): ?>
                                            <form method="post" action="actualizar_estado.php">
                                                <input type="hidden" name="folio" value="<?php echo h($solicitud["folio"]); ?>">
                                                <input type="hidden" name="nuevo_estado" value="Prestado">
                                                <button class="boton-admin boton-estado" type="submit">Marcar prestado</button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if (strcasecmp($solicitud["estado"], "Devuelto") === 0): ?>
                                            <form method="post" action="eliminar_solicitud.php"
                                                  onsubmit="return confirm('¿Eliminar definitivamente el registro #<?php echo h($solicitud["folio"]); ?>? Esta acción no se puede deshacer.');">
                                                <input type="hidden" name="folio" value="<?php echo h($solicitud["folio"]); ?>">
                                                <button class="boton-admin boton-eliminar" type="submit">Eliminar registro</button>
                                            </form>
                                        <?php elseif (strcasecmp($solicitud["estado"], "Prestado") === 0): ?>
                                            <span>Esperando devolución del alumno</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="9" class="sin-solicitudes">
                                    Todavía no hay solicitudes registradas.
                                    Cuando un alumno envíe una solicitud,
                                    aparecerá aquí.
                                </td>
                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

            <section id="devolucion" class="seccion panel">
                <h2>Devolver materiales</h2>
                <p class="subtitulo">Escribe tu número de control y el folio de tu préstamo. Puedes devolver una solicitud pendiente o un préstamo prestado. Necesitarás el folio y tu número de control.</p>
                <form id="formDevolucion">
                    <label for="controlDevolucion">Número de control</label>
                    <input id="controlDevolucion" type="text" maxlength="30" required placeholder="Tu número de control">
                    <label for="folioDevolucion">Folio de la solicitud</label>
                    <input id="folioDevolucion" type="number" min="1" required placeholder="Ej. 12">
                    <button type="submit" id="btnDevolver" class="btn-continuar">Devolver material</button>
                </form>
                <p id="mensajeDevolucion" class="mensaje" aria-live="polite"></p>
            </section>

            <hr style="margin: 30px 0;">

            <h2>Alimentar al asistente de inteligencia artificial</h2>
            <p class="admin-descripcion">Agrega conocimientos para que el asistente responda preguntas sobre el sistema. Escribe las palabras clave separadas por comas y la respuesta oficial que debe mostrar.</p>
            <?php if (($_GET["ia"] ?? "") === "guardado"): ?><p class="mensaje-panel">Conocimiento guardado correctamente.</p><?php endif; ?>
            <?php if (($_GET["ia"] ?? "") === "eliminado"): ?><p class="mensaje-panel">Conocimiento eliminado.</p><?php endif; ?>
            <?php if (($_GET["ia"] ?? "") === "error"): ?><p class="mensaje-panel">Completa tema, palabras clave y respuesta oficial.</p><?php endif; ?>

            <form class="ia-formulario" method="post" action="panel-admin.php">
                <input type="hidden" name="accion_ia" value="guardar">
                <input type="hidden" name="id_ia" value="<?php echo h($editarIA["id"] ?? ""); ?>">
                <label for="tema_ia">Tema</label>
                <input id="tema_ia" name="tema_ia" maxlength="120" required value="<?php echo h($editarIA["tema"] ?? ""); ?>" placeholder="Ej. Horario de préstamos">
                <label for="palabras_ia">Palabras clave (separadas por comas)</label>
                <input id="palabras_ia" name="palabras_ia" required value="<?php echo h(implode(", ", $editarIA["palabras_clave"] ?? [])); ?>" placeholder="horario, hora, cuándo">
                <label for="respuesta_ia">Respuesta oficial</label>
                <textarea id="respuesta_ia" name="respuesta_ia" maxlength="2000" required placeholder="Escribe la respuesta que dará el asistente."><?php echo h($editarIA["respuesta"] ?? ""); ?></textarea>
                <button class="ia-boton-guardar" type="submit"><?php echo $editarIA ? "Guardar cambios" : "Agregar conocimiento"; ?></button>
                <?php if ($editarIA): ?><a class="ia-cancelar" href="panel-admin.php">Cancelar edición</a><?php endif; ?>
            </form>

            <h2>Conocimientos registrados</h2>
            <?php if ($conocimientosIA): ?>
                <div class="ia-lista">
                    <?php foreach ($conocimientosIA as $item): ?>
                        <article class="ia-item">
                            <h3><?php echo h($item["tema"] ?? "Sin tema"); ?></h3>
                            <p><strong>Palabras clave:</strong> <?php echo h(implode(", ", $item["palabras_clave"] ?? [])); ?></p>
                            <p><strong>Respuesta oficial:</strong> <?php echo nl2br(h($item["respuesta"] ?? "")); ?></p>
                            <a href="panel-admin.php?editar_ia=<?php echo rawurlencode((string)($item["id"] ?? "")); ?>">Editar</a>
                            <form method="post" action="panel-admin.php" style="display:inline" onsubmit="return confirm('¿Eliminar este conocimiento?');">
                                <input type="hidden" name="accion_ia" value="eliminar">
                                <input type="hidden" name="id_ia" value="<?php echo h($item["id"] ?? ""); ?>">
                                <button type="submit">Eliminar</button>
                            </form>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>Todavía no hay conocimientos agregados. Usa el formulario para alimentar al asistente.</p>
            <?php endif; ?>

            <div class="admin-acciones">
                <a href="presetamosmateriales.html">Volver a la página principal</a>
                <a href="cerrar_sesion_admin.php">Cerrar sesión</a>
            </div>

        </section>

    </main>

    <!-- PIE DE PÁGINA -->
   <footer class="pie-pagina">
    <p>© 2026 CETis 120 - Sistema de Préstamo de Materiales Escolares</p>
    <p>Camila Isabela Jimenez Valencia y Cristian Ricardo Bautista Gaona</p>
    <p>Josefa Ortiz de Domínguez </p>
    <p>Todos los derechos reservados.</p>
</footer>

<script src="script.js"></script>

</body>
</html>