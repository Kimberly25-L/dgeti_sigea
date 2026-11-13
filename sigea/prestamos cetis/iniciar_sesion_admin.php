<?php
session_start();
header("Content-Type: application/json; charset=utf-8");
function responder($ok, $mensaje, $codigo = 200) {
    http_response_code($codigo);
    echo json_encode(["ok" => $ok, "mensaje" => $mensaje], JSON_UNESCAPED_UNICODE);
    exit;
}
if ($_SERVER["REQUEST_METHOD"] !== "POST") responder(false, "Método no permitido.", 405);
$datos = json_decode(file_get_contents("php://input"), true);
if (!is_array($datos)) responder(false, "Datos no válidos.", 400);
$usuario = trim((string)($datos["usuario"] ?? ""));
$contrasena = (string)($datos["contrasena"] ?? "");
// Credenciales de demostración escolar. Cámbialas antes de publicar el sistema en Internet.
if (!hash_equals("Cetis120", $usuario) || !hash_equals("administrador", $contrasena)) {
    responder(false, "Usuario o contraseña incorrectos.", 401);
}
session_regenerate_id(true);
$_SESSION["cetis120_admin"] = true;
responder(true, "Acceso autorizado.");
