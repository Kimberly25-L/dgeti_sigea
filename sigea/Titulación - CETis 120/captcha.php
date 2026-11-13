<?php
require_once "captcha_lib.php";

$formulario = preg_replace("/[^a-z_]/", "", strtolower($_GET["f"] ?? "general"));
if ($formulario === "") {
    $formulario = "general";
}

$codigo = captcha_generar($formulario);
$col = ["#b32424", "#08472e", "#8a5a00", "#104587"];

$s = '<svg xmlns="http://www.w3.org/2000/svg" width="170" height="56" viewBox="0 0 170 56">'
   . '<rect width="170" height="56" rx="8" fill="#f5efe6"/>';

for ($i = 0; $i < 6; $i++) {
    $s .= sprintf(
        '<line x1="%d" y1="%d" x2="%d" y2="%d" stroke="#94a3b8" stroke-width="1.5" opacity=".6"/>',
        random_int(0, 170), random_int(0, 56), random_int(0, 170), random_int(0, 56)
    );
}
for ($i = 0; $i < 30; $i++) {
    $s .= sprintf(
        '<circle cx="%d" cy="%d" r="1.2" fill="#94a3b8" opacity=".6"/>',
        random_int(0, 170), random_int(0, 56)
    );
}
for ($i = 0; $i < CAPTCHA_LONGITUD; $i++) {
    $x = 15 + $i * 29;
    $s .= sprintf(
        '<text x="%d" y="%d" font-size="30" font-weight="800" font-family="monospace" fill="%s" transform="rotate(%d %d 35)">%s</text>',
        $x, random_int(34, 42), $col[array_rand($col)], random_int(-22, 22), $x, $codigo[$i]
    );
}

header("Content-Type: image/svg+xml; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
echo $s . "</svg>";
