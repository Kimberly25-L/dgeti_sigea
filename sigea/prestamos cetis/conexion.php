<?php
$servidor = "sql313.infinityfree.com";
$baseDatos = "if0_43367189_db_prestamos";
$usuario = "if0_43367189";
$contrasena = "B8F8gAOeYEk";

try {
    $pdo = new PDO(
        "mysql:host=$servidor;dbname=$baseDatos;charset=utf8mb4",
        $usuario,
        $contrasena,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit("Error de conexión con la base de datos.");
}