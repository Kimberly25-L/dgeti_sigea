<?php

$servidor = "sql313.infinityfree.com";
$usuario = "if0_43367189";
$password = "B8F8gAOeYEk";
$baseDatos = "if0_43367189_db_titulacion";

$conexion = new mysqli(
    $servidor,
    $usuario,
    $password,
    $baseDatos
);

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$conexion->set_charset("utf8mb4");

?>

