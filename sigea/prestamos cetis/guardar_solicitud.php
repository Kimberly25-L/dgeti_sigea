<?php

header("Content-Type: application/json; charset=utf-8");

function responder($ok, $mensaje, $codigo = 200, $folio = null) {
    http_response_code($codigo);

    $respuesta = [
        "ok" => $ok,
        "mensaje" => $mensaje
    ];

    if ($folio !== null) {
        $respuesta["folio"] = $folio;
    }

    echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    responder(false, "Método no permitido.", 405);
}

try {
    require_once "conexion.php";

    $contenido = file_get_contents("php://input");
    $datos = json_decode($contenido, true);

    if (!is_array($datos)) {
        responder(false, "No se recibieron datos válidos.", 400);
    }

    $nombre = trim($datos["nombre"] ?? "");
    $control = trim($datos["control"] ?? "");
    $password = (string)($datos["password"] ?? "");
    $especialidad = trim($datos["especialidad"] ?? "");
    $turno = trim($datos["turno"] ?? "");
    $tiempo = trim($datos["horario"] ?? "");
    $materiales = $datos["materiales"] ?? [];

    $especialidades = [
        "Programación",
        "Contabilidad",
        "Electrónica",
        "Puericultura",
        "Ventas",
        "Hospedaje",
        "Cosmetología",
        "Comercio internacional y aduanas",
        "Ciberseguridad",
        "Inteligencia artificial",
        "E-Commerce"
    ];

    $turnos = ["Matutino", "Vespertino"];

    $tiempos = [
        "30 minutos",
        "1 hora",
        "2 horas",
        "2 a 5 horas"
    ];

    $catalogo = [
        "Computadoras",
        "Cargadores",
        "Adaptadores",
        "Calculadoras",
        "Cables HDMI",
        "Cables de red",
        "Extensiones",
        "Proyectores",
        "Routers",
        "Cables de alimentación"
    ];

    if (
        $nombre === "" ||
        $control === "" ||
        $especialidad === "" ||
        $turno === "" ||
        $tiempo === ""
    ) {
        responder(false, "Completa todos los campos.", 400);
    }

    if (!preg_match('/^[0-9]{14}$/', $control)) {
        responder(false, "El número de control debe contener exactamente 14 dígitos.", 400);
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS cuentas_alumnos (numero_control VARCHAR(14) NOT NULL PRIMARY KEY, nombre_alumno VARCHAR(150) NOT NULL, especialidad VARCHAR(100) NOT NULL, turno VARCHAR(20) NOT NULL, password_hash VARCHAR(255) NOT NULL, creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $cuentaQ = $pdo->prepare("SELECT * FROM cuentas_alumnos WHERE numero_control = ?");
    $cuentaQ->execute([$control]);
    $cuenta = $cuentaQ->fetch();
    if (!$cuenta || !password_verify($password, $cuenta["password_hash"]) || mb_strtolower(trim($cuenta["nombre_alumno"])) !== mb_strtolower($nombre) || $cuenta["especialidad"] !== $especialidad || $cuenta["turno"] !== $turno) {
        responder(false, "Inicia sesión con tu cuenta y utiliza los datos registrados en ella.", 401);
    }

    if (
        mb_strlen($nombre) > 150 ||
        mb_strlen($control) > 14
    ) {
        responder(false, "El nombre o número de control es demasiado largo.", 400);
    }

    if (!in_array($especialidad, $especialidades, true)) {
        responder(false, "La especialidad seleccionada no es válida.", 400);
    }

    if (!in_array($turno, $turnos, true)) {
        responder(false, "El turno seleccionado no es válido.", 400);
    }

    if (!in_array($tiempo, $tiempos, true)) {
        responder(false, "El tiempo de uso seleccionado no es válido.", 400);
    }

    if (!is_array($materiales)) {
        responder(false, "La lista de materiales no es válida.", 400);
    }

    $materiales = array_values(array_unique($materiales));

    if (count($materiales) < 1 || count($materiales) > 2) {
        responder(false, "Selecciona uno o dos materiales.", 400);
    }

    foreach ($materiales as $material) {
        if (!is_string($material) || !in_array($material, $catalogo, true)) {
            responder(false, "Hay un material que no pertenece al catálogo.", 400);
        }
    }

    $materialesTexto = implode(", ", $materiales);

    // Serializa solicitudes concurrentes bloqueando las filas del inventario.
    // Así dos alumnos no pueden tomar simultáneamente la última unidad.
    $pdo->beginTransaction();

    // Un alumno solo puede tener una solicitud activa a la vez.
    // Se consideran activas las solicitudes Pendientes y las que ya fueron Prestadas.
    // La revisión se hace dentro de la transacción antes de reservar disponibilidad.
    $solicitudActivaQ = $pdo->prepare(
        "SELECT folio, estado FROM solicitudes
         WHERE numero_control = ? AND estado IN ('Pendiente', 'Prestado')
         ORDER BY folio ASC LIMIT 1 FOR UPDATE"
    );
    $solicitudActivaQ->execute([$control]);
    $solicitudActiva = $solicitudActivaQ->fetch();

    if ($solicitudActiva) {
        $pdo->rollBack();
        responder(
            false,
            "No puedes realizar otra solicitud porque tienes un préstamo o una solicitud pendiente (folio " . $solicitudActiva["folio"] . "). Registra la devolución del material anterior y espera a que quede como Devuelto.",
            409,
            $solicitudActiva["folio"]
        );
    }

    $filasMateriales = $pdo->query(
        "SELECT nombre, cantidad_total FROM materiales ORDER BY id_material FOR UPDATE"
    )->fetchAll();
    $catalogoBD = [];
    foreach ($filasMateriales as $filaMaterial) {
        $catalogoBD[$filaMaterial["nombre"]] = (int)$filaMaterial["cantidad_total"];
    }

    $conteoActivos = array_fill_keys(array_keys($catalogoBD), 0);
    $activos = $pdo->query(
        "SELECT materiales FROM solicitudes WHERE estado IN ('Pendiente', 'Prestado')"
    )->fetchAll();
    foreach ($activos as $activo) {
        foreach (explode(",", $activo["materiales"]) as $nombreActivo) {
            $nombreActivo = trim($nombreActivo);
            if (isset($conteoActivos[$nombreActivo])) $conteoActivos[$nombreActivo]++;
        }
    }

    foreach ($materiales as $materialSolicitado) {
        if (!isset($catalogoBD[$materialSolicitado])) {
            $pdo->rollBack();
            responder(false, "El material no existe en el inventario de la base de datos.", 400);
        }
        if ($conteoActivos[$materialSolicitado] >= $catalogoBD[$materialSolicitado]) {
            $pdo->rollBack();
            responder(false, "Ya no hay unidades disponibles de: " . $materialSolicitado, 409);
        }
        $conteoActivos[$materialSolicitado]++;
    }

    $sql = "
        INSERT INTO solicitudes
        (
            nombre_alumno,
            numero_control,
            especialidad,
            turno,
            tiempo_uso,
            materiales
        )
        VALUES
        (
            :nombre,
            :control,
            :especialidad,
            :turno,
            :tiempo,
            :materiales
        )
    ";

    $consulta = $pdo->prepare($sql);

    $consulta->execute([
        ":nombre" => $nombre,
        ":control" => $control,
        ":especialidad" => $especialidad,
        ":turno" => $turno,
        ":tiempo" => $tiempo,
        ":materiales" => $materialesTexto
    ]);
    $folioNuevo = $pdo->lastInsertId();
    $pdo->commit();

    responder(
        true,
        "Solicitud registrada correctamente.",
        200,
        $folioNuevo
    );

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    error_log("Error al guardar solicitud: " . $e->getMessage());

    responder(
        false,
        "No se pudo guardar la solicitud. Revisa la base de datos y sus columnas.",
        500
    );
}