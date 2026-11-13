<?php

$servidor = "sql313.infinityfree.com";
$usuario = "if0_43367189";
$password = "B8F8gAOeYEk";
$bd = "if0_43367189_db_titulacionia";

$conexion_ia = new mysqli(
    $servidor,
    $usuario,
    $password,
    $bd
);

$conexion_ia->set_charset("utf8mb4");

if ($conexion_ia->connect_error) {
    die("Error de conexión con la IA: " . $conexion_ia->connect_error);
}

?>