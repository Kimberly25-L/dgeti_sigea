<?php
session_start();
if (empty($_SESSION["cetis120_admin"])) {
    header("Location: presetamosmateriales.html?admin=login");
    exit;
}
require_once "conexion.php";

function volver($mensaje) {
    header("Location: panel-admin.php?mensaje=" . urlencode($mensaje));
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") volver("Solicitud no válida.");

$folio = filter_input(INPUT_POST, "folio", FILTER_VALIDATE_INT);
if (!$folio || $folio < 1) volver("Folio no válido.");

try {
    $consulta = $pdo->prepare("DELETE FROM solicitudes WHERE folio = ? AND estado = 'Devuelto'");
    $consulta->execute([$folio]);

    if ($consulta->rowCount() !== 1) {
        volver("No se eliminó el registro: debe existir y estar marcado como Devuelto.");
    }

    volver("El registro devuelto fue eliminado correctamente.");
} catch (Throwable $e) {
    error_log("Error al eliminar solicitud: " . $e->getMessage());
    volver("No se pudo eliminar el registro.");
}
