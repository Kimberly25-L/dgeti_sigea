<?php
header("Content-Type: application/json; charset=utf-8");

require_once "conexion.php";

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

try {
    $consulta = $pdo->query(
        "SELECT materiales
         FROM solicitudes
         WHERE estado IN ('Pendiente', 'Prestado')"
    );

    $prestados = array_fill_keys(array_keys($catalogo), 0);

    foreach ($consulta->fetchAll() as $solicitud) {
        $lista = explode(",", $solicitud["materiales"]);

        foreach ($lista as $nombre) {
            $nombre = trim($nombre);

            if (isset($prestados[$nombre])) {
                $prestados[$nombre]++;
            }
        }
    }

    $resultado = [];

    foreach ($catalogo as $nombre => $total) {
        $resultado[] = [
            "nombre" => $nombre,
            "total" => $total,
            "prestados" => $prestados[$nombre],
            "disponible" => max(0, $total - $prestados[$nombre])
        ];
    }

    echo json_encode(
        ["ok" => true, "materiales" => $resultado],
        JSON_UNESCAPED_UNICODE
    );
} catch (Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);

    echo json_encode([
        "ok" => false,
        "mensaje" => "No se pudo consultar el inventario."
    ], JSON_UNESCAPED_UNICODE);
}