<?php
header("Content-Type: application/json; charset=utf-8");
function salida($ok, $mensaje, $codigo=200) {
    http_response_code($codigo);
    echo json_encode(["ok"=>$ok, "mensaje"=>$mensaje], JSON_UNESCAPED_UNICODE);
    exit;
}
if ($_SERVER["REQUEST_METHOD"] !== "POST") salida(false, "Método no permitido.", 405);
try {
    require_once "conexion.php";
    $d = json_decode(file_get_contents("php://input"), true);
    if (!is_array($d)) salida(false, "Datos no válidos.", 400);
    $folio = (int)($d["folio"] ?? 0);
    $control = trim($d["control"] ?? "");
    $password = (string)($d["password"] ?? "");
    if ($folio < 1 || !preg_match('/^[0-9]{14}$/', $control) || $password === "") {
        salida(false, "Ingresa un folio, número de control y contraseña válidos.", 400);
    }
    $q = $pdo->prepare("SELECT password_hash FROM cuentas_alumnos WHERE numero_control=?");
    $q->execute([$control]);
    $cuenta = $q->fetch();
    if (!$cuenta || !password_verify($password, $cuenta["password_hash"])) {
        salida(false, "El número de control o la contraseña son incorrectos.", 401);
    }
    $q = $pdo->prepare("UPDATE solicitudes SET estado='Cancelado' WHERE folio=? AND numero_control=? AND estado='Pendiente'");
    $q->execute([$folio, $control]);
    if ($q->rowCount() !== 1) salida(false, "No se pudo cancelar. Solo puedes cancelar solicitudes que sigan pendientes.", 409);
    salida(true, "La solicitud fue cancelada correctamente.");
} catch (Throwable $e) {
    error_log("Cancelar solicitud alumno: " . $e->getMessage());
    salida(false, "No se pudo cancelar la solicitud.", 500);
}
