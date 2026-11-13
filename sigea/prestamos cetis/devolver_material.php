<?php
header("Content-Type: application/json; charset=utf-8");

function responder($ok, $mensaje, $codigo = 200, $extra = []) {
    http_response_code($codigo);
    echo json_encode(array_merge(["ok" => $ok, "mensaje" => $mensaje], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    responder(false, "Método no permitido.", 405);
}

try {
    require_once "conexion.php";
    $datos = json_decode(file_get_contents("php://input"), true);

    if (!is_array($datos)) responder(false, "Datos no válidos.", 400);

    $control = trim((string)($datos["control"] ?? ""));
    $folio = filter_var($datos["folio"] ?? null, FILTER_VALIDATE_INT);

    if ($control === "" || !$folio || $folio < 1) {
        responder(false, "Ingresa un número de control y un folio válidos.", 400);
    }

    $pdo->beginTransaction();

    $consulta = $pdo->prepare(
        "SELECT folio, numero_control, materiales, estado
         FROM solicitudes WHERE folio = ? FOR UPDATE"
    );
    $consulta->execute([$folio]);
    $solicitud = $consulta->fetch();

    if (!$solicitud || !hash_equals((string)$solicitud["numero_control"], $control)) {
        $pdo->rollBack();
        responder(false, "No se encontró un préstamo con ese folio y número de control.", 404);
    }

    $estadoActual = mb_strtolower(trim((string)$solicitud["estado"]), "UTF-8");
    if ($estadoActual === "devuelto") {
        $pdo->rollBack();
        responder(false, "Este préstamo ya fue devuelto. No se modificó el inventario.", 409);
    }
    if ($estadoActual !== "prestado") {
        $pdo->rollBack();
        responder(false, "Tu solicitud todavía está como Pendiente. Pide al administrador que la marque como Prestado antes de devolver el material. No se modificó el inventario.", 409);
    }

    $actualizar = $pdo->prepare("UPDATE solicitudes SET estado = 'Devuelto' WHERE folio = ?");
    $actualizar->execute([$folio]);
    $pdo->commit();

    responder(true, "Devolución registrada correctamente.", 200, [
        "folio" => (int)$solicitud["folio"],
        "materiales" => $solicitud["materiales"],
        "estado" => "Devuelto"
    ]);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    error_log("Error al devolver material: " . $e->getMessage());
    responder(false, "No se pudo procesar la devolución. Revisa la conexión con la base de datos.", 500);
}
