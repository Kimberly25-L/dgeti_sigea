<?php
header("Content-Type: application/json; charset=utf-8");
function responder($ok,$mensaje,$codigo=200,$folio=null){http_response_code($codigo);$r=["ok"=>$ok,"mensaje"=>$mensaje];if($folio!==null)$r["folio"]=$folio;echo json_encode($r,JSON_UNESCAPED_UNICODE);exit;}
if($_SERVER["REQUEST_METHOD"]!=="POST") responder(false,"Método no permitido.",405);
try {
 require_once "conexion.php";
 $d=json_decode(file_get_contents("php://input"),true);
 if(!is_array($d)) responder(false,"Datos no válidos.",400);
 $folio=(int)($d["folio"]??0);$controlAnterior=trim($d["controlAnterior"]??$d["control"]??"");$nombre=trim($d["nombre"]??"");$control=trim($d["control"]??"");$especialidad=trim($d["especialidad"]??"");$turno=trim($d["turno"]??"");$tiempo=trim($d["horario"]??"");$password=(string)($d["password"]??"");$materiales=$d["materiales"]??[];
 if($folio<1||$nombre===""||!preg_match('/^[0-9]{14}$/',$control)||$especialidad===""||!in_array($turno,["Matutino","Vespertino"],true)||!in_array($tiempo,["30 minutos","1 hora","2 horas","2 a 5 horas"],true)||!is_array($materiales)||count($materiales)<1||count($materiales)>2) responder(false,"Revisa los campos de la solicitud.",400);
 $pdo->exec("CREATE TABLE IF NOT EXISTS cuentas_alumnos (numero_control VARCHAR(14) NOT NULL PRIMARY KEY, nombre_alumno VARCHAR(150) NOT NULL, especialidad VARCHAR(100) NOT NULL, turno VARCHAR(20) NOT NULL, password_hash VARCHAR(255) NOT NULL, creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
 $q=$pdo->prepare("SELECT * FROM cuentas_alumnos WHERE numero_control=?");$q->execute([$control]);$cuenta=$q->fetch();if(!$cuenta||!password_verify($password,$cuenta["password_hash"])) responder(false,"La contraseña o el número de control no son correctos.",401);
 $q=$pdo->prepare("SELECT * FROM solicitudes WHERE folio=? AND numero_control=?");$q->execute([$folio,$controlAnterior]);$sol=$q->fetch();if(!$sol) responder(false,"No se encontró tu solicitud para editar.",404);if($sol["estado"]!=="Pendiente") responder(false,"Solo puedes editar una solicitud que siga pendiente.",409);
 $catalogo=["Computadoras","Cargadores","Adaptadores","Calculadoras","Cables HDMI","Cables de red","Extensiones","Proyectores","Routers","Cables de alimentación"];$materiales=array_values(array_unique($materiales));foreach($materiales as $m)if(!is_string($m)||!in_array($m,$catalogo,true))responder(false,"Material no válido.",400);
 $texto=implode(", ",$materiales);
 $q=$pdo->prepare("UPDATE solicitudes SET nombre_alumno=?, numero_control=?, especialidad=?, turno=?, tiempo_uso=?, materiales=? WHERE folio=? AND numero_control=? AND estado='Pendiente'");$q->execute([$nombre,$control,$especialidad,$turno,$tiempo,$texto,$folio,$controlAnterior]);
 responder(true,"Solicitud actualizada correctamente. Conservas el mismo folio.",200,$folio);
} catch(Throwable $e){error_log("Editar solicitud: ".$e->getMessage());responder(false,"No se pudo actualizar la solicitud.",500);}
