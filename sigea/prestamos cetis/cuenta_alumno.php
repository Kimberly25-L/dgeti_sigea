<?php
header("Content-Type: application/json; charset=utf-8");
function salida($ok,$mensaje,$codigo=200){http_response_code($codigo);echo json_encode(["ok"=>$ok,"mensaje"=>$mensaje],JSON_UNESCAPED_UNICODE);exit;}
if($_SERVER["REQUEST_METHOD"]!=="POST") salida(false,"Método no permitido.",405);
try {
 require_once "conexion.php";
 $d=json_decode(file_get_contents("php://input"),true);
 if(!is_array($d)) salida(false,"Datos no válidos.",400);
 $accion=$d["accion"]??""; $nombre=trim($d["nombre"]??""); $control=trim($d["control"]??""); $especialidad=trim($d["especialidad"]??""); $turno=trim($d["turno"]??""); $password=(string)($d["password"]??"");
 if($nombre===""||!preg_match('/^[0-9]{14}$/',$control)||$especialidad===""||!in_array($turno,["Matutino","Vespertino"],true)||strlen($password)<6) salida(false,"Completa los datos; el número de control debe tener 14 dígitos y la contraseña al menos 6 caracteres.",400);
 $pdo->exec("CREATE TABLE IF NOT EXISTS cuentas_alumnos (numero_control VARCHAR(14) NOT NULL PRIMARY KEY, nombre_alumno VARCHAR(150) NOT NULL, especialidad VARCHAR(100) NOT NULL, turno VARCHAR(20) NOT NULL, password_hash VARCHAR(255) NOT NULL, creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
 $q=$pdo->prepare("SELECT * FROM cuentas_alumnos WHERE numero_control=?");$q->execute([$control]);$cuenta=$q->fetch();
 if($accion==="crear") {
   if($cuenta) salida(false,"Ya existe una cuenta con ese número de control. Inicia sesión con tu contraseña.",409);
   $q=$pdo->prepare("INSERT INTO cuentas_alumnos(numero_control,nombre_alumno,especialidad,turno,password_hash) VALUES(?,?,?,?,?)");$q->execute([$control,$nombre,$especialidad,$turno,password_hash($password,PASSWORD_DEFAULT)]);
   salida(true,"Cuenta creada correctamente. Guarda tu contraseña: la necesitarás para solicitar materiales nuevamente.");
 }
 if($accion==="entrar") {
   if(!$cuenta || !password_verify($password,$cuenta["password_hash"])) salida(false,"Número de control o contraseña incorrectos.",401);
   if(mb_strtolower(trim($cuenta["nombre_alumno"]))!==mb_strtolower($nombre)||$cuenta["especialidad"]!==$especialidad||$cuenta["turno"]!==$turno) salida(false,"El nombre, la especialidad y el turno deben coincidir exactamente con los datos de la cuenta.",401);
   salida(true,"Sesión iniciada. Ya puedes registrar una solicitud.");
 }
 if($accion==="actualizar") {
   $controlAnterior=trim($d["controlAnterior"]??"");
   $q=$pdo->prepare("SELECT * FROM cuentas_alumnos WHERE numero_control=?");
   $q->execute([$controlAnterior]); $cuentaAnterior=$q->fetch();
   if(!$cuentaAnterior || !password_verify($password,$cuentaAnterior["password_hash"])) salida(false,"El número de control o la contraseña no son correctos.",401);
   if($control!==$controlAnterior) {
     $q=$pdo->prepare("SELECT numero_control FROM cuentas_alumnos WHERE numero_control=?");
     $q->execute([$control]); if($q->fetch()) salida(false,"Ese número de control ya está registrado en otra cuenta.",409);
   }
   $q=$pdo->prepare("UPDATE cuentas_alumnos SET numero_control=?, nombre_alumno=?, especialidad=?, turno=? WHERE numero_control=?");
   $q->execute([$control,$nombre,$especialidad,$turno,$controlAnterior]);
   salida(true,"Datos de la cuenta verificados y actualizados.");
 }
 salida(false,"Acción no válida.",400);
} catch(Throwable $e){error_log("Cuenta alumno: ".$e->getMessage());salida(false,"No se pudo procesar la cuenta. Revisa la base de datos.",500);}
