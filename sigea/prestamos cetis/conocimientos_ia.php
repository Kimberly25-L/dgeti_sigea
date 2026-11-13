<?php
header("Content-Type: application/json; charset=utf-8");
$archivo = __DIR__ . "/conocimientos_ia.json";
if (!is_file($archivo)) {
    echo "[]";
    exit;
}
$contenido = file_get_contents($archivo);
$datos = json_decode($contenido, true);
echo json_encode(is_array($datos) ? $datos : [], JSON_UNESCAPED_UNICODE);
