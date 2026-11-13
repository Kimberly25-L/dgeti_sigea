<?php

require_once "../conexion_ia.php";

/* --------------------------------
   NORMALIZAR TEXTO
-------------------------------- */

function normalizar($texto) {
    $texto = mb_strtolower($texto, "UTF-8");

    $texto = strtr($texto, [
        "á" => "a",
        "é" => "e",
        "í" => "i",
        "ó" => "o",
        "ú" => "u",
        "ü" => "u"
    ]);

    $texto = preg_replace(
        '/[^\p{L}\p{N}\s]/u',
        ' ',
        $texto
    );

    return trim(preg_replace('/\s+/', ' ', $texto));
}

/* --------------------------------
   RECIBIR PREGUNTA
-------------------------------- */

$preguntaOriginal = trim($_GET["pregunta"] ?? "");

if ($preguntaOriginal === "") {
    echo "No se recibió ninguna pregunta.";
    exit();
}

$pregunta = normalizar($preguntaOriginal);

/* --------------------------------
   SALUDOS
-------------------------------- */

$saludos = [
    "hola",
    "buenas",
    "buenos dias",
    "buenas tardes",
    "buenas noches"
];

if (in_array($pregunta, $saludos, true)) {
    echo "¡Hola! 👋 Somos Jaguares, los asistentes del proceso de titulación del CETis 120. ¿En qué podemos ayudarte?";
    exit();
}

/* --------------------------------
   DETECTAR MODALIDAD
-------------------------------- */

$modalidad = null;

if (
    strpos($pregunta, "experiencia laboral") !== false ||
    strpos($pregunta, "experiencia de trabajo") !== false
) {
    $modalidad = "experiencia";
} elseif (
    strpos($pregunta, "prototipo") !== false ||
    strpos($pregunta, "proyecto") !== false
) {
    $modalidad = "prototipo";
} elseif (strpos($pregunta, "promedio") !== false) {
    $modalidad = "promedio";
}

/* --------------------------------
   BUSCAR CONOCIMIENTOS ESPECIFICOS
-------------------------------- */

if (
    strpos($pregunta, "fotografia") !== false ||
    strpos($pregunta, "fotografias") !== false ||
    strpos($pregunta, "foto") !== false ||
    strpos($pregunta, "fotos") !== false
) {
    $sqlFotos = "SELECT titulo, contenido
                 FROM conocimientos
                 WHERE activo = 1
                 AND (
                     titulo LIKE '%fotografía%'
                     OR titulo LIKE '%fotografia%'
                 )
                 LIMIT 1";

    $resultadoFotos = $conexion_ia->query($sqlFotos);

    if ($resultadoFotos && $resultadoFotos->num_rows > 0) {
        $filaFotos = $resultadoFotos->fetch_assoc();

        echo "<h3>" .
            htmlspecialchars($filaFotos["titulo"], ENT_QUOTES, "UTF-8") .
            "</h3>";

        echo "<p>" .
            htmlspecialchars($filaFotos["contenido"], ENT_QUOTES, "UTF-8") .
            "</p>";

        exit();
    }
}

/* --------------------------------
   PALABRAS POCO ÚTILES
-------------------------------- */

$ignoradas = [
    "que", "cual", "cuales", "como", "cuando",
    "donde", "para", "por", "con", "sin", "de",
    "del", "la", "las", "el", "los", "un", "una",
    "unos", "unas", "y", "o", "me", "mi", "mis",
    "necesito", "necesita", "quiero", "quisiera",
    "puedo", "debo", "tengo", "hay", "es", "son",
    "se", "mi", "mis", "el", "la", "lo",
    "dime", "explicame", "oye"
];

$palabrasUsuario = array_values(array_filter(
    explode(" ", $pregunta),
    function ($palabra) use ($ignoradas) {
        return $palabra !== ""
            && !in_array($palabra, $ignoradas, true);
    }
));

/* --------------------------------
   CONSULTAR CONOCIMIENTOS Y FAQ
-------------------------------- */

$sql = "SELECT
            pf.pregunta AS pregunta_frecuente,
            c.id,
            c.titulo,
            c.contenido,
            c.palabras_clave
        FROM preguntas_frecuentes pf
        INNER JOIN conocimientos c
            ON pf.conocimiento_id = c.id
        WHERE pf.activo = 1
          AND c.activo = 1";

$resultado = $conexion_ia->query($sql);

if (!$resultado) {
    error_log("Error en buscar.php: " . $conexion_ia->error);
    echo "<p>Ocurrió un error al consultar la información. Inténtalo más tarde.</p>";
    exit();
}


$mejorResultado = null;
$mejorPuntuacion = 0;

while ($fila = $resultado->fetch_assoc()) {

    $faq = normalizar($fila["pregunta_frecuente"]);
    $titulo = normalizar($fila["titulo"]);

    $puntuacion = 0;

    /* --------------------------------
       1. COINCIDENCIA CON LA PREGUNTA
    -------------------------------- */

    // Una pregunta idéntica tiene prioridad.
    if ($pregunta === $faq) {
        $puntuacion += 30;
    } elseif (
        strpos($pregunta, $faq) !== false ||
        strpos($faq, $pregunta) !== false
    ) {
        // Coincidencia parcial de la frase completa.
        $puntuacion += 15;
    }

    /* --------------------------------
       2. COINCIDENCIA POR PALABRAS
    -------------------------------- */

    $palabrasFAQ = array_unique(explode(" ", $faq));

    foreach ($palabrasUsuario as $palabra) {
        if (in_array($palabra, $palabrasFAQ, true)) {
            $puntuacion += 2;
        }
    }

    /* --------------------------------
       3. PALABRAS CLAVE
    -------------------------------- */

    $claves = array_map(
        "normalizar",
        explode(",", $fila["palabras_clave"] ?? "")
    );

    foreach ($claves as $clave) {

        $clave = trim($clave);

        if ($clave === "") {
            continue;
        }

        // Comparar frases completas, no coincidencias parciales.
        $preguntaConEspacios = " " . $pregunta . " ";
        $claveConEspacios = " " . $clave . " ";

        if (strpos($preguntaConEspacios, $claveConEspacios) !== false) {

            // Las frases específicas aportan más información.
            if (strpos($clave, " ") !== false) {
                $puntuacion += 8;
            } else {
                $puntuacion += 2;
            }
        }
    }

    /* --------------------------------
       4. COINCIDENCIA CON EL TÍTULO
    -------------------------------- */

    $palabrasTitulo = array_unique(explode(" ", $titulo));

    foreach ($palabrasUsuario as $palabra) {
        if (in_array($palabra, $palabrasTitulo, true)) {
            $puntuacion += 3;
        }
    }

    /* --------------------------------
       5. PRIORIZAR LA MODALIDAD
    -------------------------------- */

    if (
        $modalidad === "promedio" &&
        strpos($titulo, "promedio") !== false
    ) {
        $puntuacion += 3;
    }

    if (
        $modalidad === "prototipo" &&
        (
            strpos($titulo, "prototipo") !== false ||
            strpos($titulo, "proyecto") !== false
        )
    ) {
        $puntuacion += 3;
    }

    if (
        $modalidad === "experiencia" &&
        strpos($titulo, "experiencia laboral") !== false
    ) {
        $puntuacion += 3;
    }

    /* --------------------------------
       6. GUARDAR LA MEJOR COINCIDENCIA
    -------------------------------- */

    if ($puntuacion > $mejorPuntuacion) {
        $mejorPuntuacion = $puntuacion;
        $mejorResultado = $fila;
    }
}
    

/* --------------------------------
   RESPONDER
-------------------------------- */

if ($mejorResultado !== null && $mejorPuntuacion > 0) {

    echo "<h3>" .
        htmlspecialchars($mejorResultado["titulo"], ENT_QUOTES, "UTF-8") .
        "</h3>";

    echo "<p>" .
        nl2br(htmlspecialchars(
            $mejorResultado["contenido"],
            ENT_QUOTES,
            "UTF-8"
        )) .
        "</p>";

} else {

    echo "<p>No encontré información suficiente para responder esa pregunta.</p>";

    echo "<p>Puedo ayudarte con información sobre:</p>";

    echo "<ul>";
    echo "<li>Modalidad por Promedio</li>";
    echo "<li>Modalidad por Prototipo/Proyecto</li>";
    echo "<li>Modalidad por Experiencia Laboral</li>";
    echo "<li>Documentos y requisitos</li>";
    echo "<li>Proceso de titulación</li>";
    echo "</ul>";
}

?>
