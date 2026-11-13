<?php
session_start();
require_once "conexion.php";

function volver($mensaje) {
    header(
        "Location: panel-admin.php?mensaje=" .
        urlencode($mensaje)
    );
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    volver("Solicitud no válida.");
}

$folio = filter_input(INPUT_POST, "folio", FILTER_VALIDATE_INT);
$nuevoEstado = $_POST["nuevo_estado"] ?? "";

if (!$folio || !in_array($nuevoEstado, ["Prestado", "Devuelto"], true)) {
    volver("Los datos enviados no son válidos.");
}

try {
    $pdo->beginTransaction();

    $consulta = $pdo->prepare(
        "SELECT folio, materiales, estado
         FROM solicitudes
         WHERE folio = ?
         FOR UPDATE"
    );
    $consulta->execute([$folio]);
    $solicitud = $consulta->fetch();

    if (!$solicitud) {
        throw new RuntimeException("No se encontró la solicitud.");
    }

    $estadoActual = $solicitud["estado"];

    if ($nuevoEstado === "Prestado") {
        if ($estadoActual !== "Pendiente") {
            throw new RuntimeException(
                "Solo las solicitudes pendientes pueden marcarse como prestadas."
            );
        }

        $catalogo = [
            "Computadoras" => 3,
            "Cargadores" => 3,
            "Adaptadores" => 3,
            "Calculadoras" => 3,
            "Cables HDMI" => 3,
            "Cables de red" => 3,
            "Extensiones" => 3,
            "Proyectores" => 3,
            "Routers" => 3,
            "Cables de alimentación" => 3
        ];

        $materialesSolicitud = array_map(
            "trim",
            explode(",", $solicitud["materiales"])
        );

        $consultaActivos = $pdo->prepare(
            "SELECT materiales
             FROM solicitudes
             WHERE estado IN ('Pendiente', 'Prestado') AND folio <> ?"
        );
        $consultaActivos->execute([$folio]);

        $conteo = array_fill_keys(array_keys($catalogo), 0);

        foreach ($consultaActivos->fetchAll() as $fila) {
            foreach (explode(",", $fila["materiales"]) as $nombre) {
                $nombre = trim($nombre);

                if (isset($conteo[$nombre])) {
                    $conteo[$nombre]++;
                }
            }
        }

        foreach ($materialesSolicitud as $nombre) {
            if (!isset($catalogo[$nombre])) {
                throw new RuntimeException(
                    "Hay un material que no está en el catálogo."
                );
            }

            if ($conteo[$nombre] >= $catalogo[$nombre]) {
                throw new RuntimeException(
                    "No hay unidades disponibles de: " . $nombre
                );
            }

            // Reservar la unidad para evitar superar el inventario
            $conteo[$nombre]++;
        }
    } else {
        if ($estadoActual !== "Prestado") {
            throw new RuntimeException(
                "Solo se pueden devolver materiales que estén prestados."
            );
        }
    }

    $actualizar = $pdo->prepare(
        "UPDATE solicitudes SET estado = ? WHERE folio = ?"
    );
    $actualizar->execute([$nuevoEstado, $folio]);

    $pdo->commit();

    volver(
        $nuevoEstado === "Prestado"
            ? "Préstamo registrado correctamente."
            : "Devolución registrada correctamente."
    );

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());
    volver($e->getMessage());
}