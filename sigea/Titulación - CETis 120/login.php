<?php

session_start();

require_once "conexion.php";
require_once "captcha_lib.php";
require_once "captcha_widget.php";

const MAX_INTENTOS   = 2;   // intentos fallidos permitidos
const MINUTOS_BLOQUEO = 15; // tiempo de bloqueo

/* Tabla de intentos (se crea sola la primera vez) */
$conexion->query(
    "CREATE TABLE IF NOT EXISTS intentos_login (
        usuario VARCHAR(50) NOT NULL PRIMARY KEY,
        intentos INT NOT NULL DEFAULT 0,
        bloqueado_hasta DATETIME NULL,
        ultimo_intento DATETIME NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
);

function minutosRestantes(mysqli $c, string $clave): int
{
    $st = $c->prepare(
        "SELECT CEIL(TIMESTAMPDIFF(SECOND, NOW(), bloqueado_hasta) / 60)
         FROM intentos_login
         WHERE usuario = ? AND bloqueado_hasta > NOW()"
    );
    $st->bind_param("s", $clave);
    $st->execute();
    $st->bind_result($min);
    $hayBloqueo = $st->fetch();
    $st->close();

    return $hayBloqueo ? max(1, (int) $min) : 0;
}

function registrarFallo(mysqli $c, string $clave): int
{
    $st = $c->prepare(
        "INSERT INTO intentos_login (usuario, intentos, ultimo_intento)
         VALUES (?, 1, NOW())
         ON DUPLICATE KEY UPDATE intentos = intentos + 1, ultimo_intento = NOW()"
    );
    $st->bind_param("s", $clave);
    $st->execute();
    $st->close();

    $st = $c->prepare("SELECT intentos FROM intentos_login WHERE usuario = ?");
    $st->bind_param("s", $clave);
    $st->execute();
    $st->bind_result($n);
    $st->fetch();
    $st->close();

    if ((int) $n >= MAX_INTENTOS) {
        $min = MINUTOS_BLOQUEO;
        $st = $c->prepare(
            "UPDATE intentos_login
             SET intentos = 0,
                 bloqueado_hasta = DATE_ADD(NOW(), INTERVAL ? MINUTE)
             WHERE usuario = ?"
        );
        $st->bind_param("is", $min, $clave);
        $st->execute();
        $st->close();
    }

    return (int) $n;
}

function limpiarIntentos(mysqli $c, string $clave): void
{
    $st = $c->prepare("DELETE FROM intentos_login WHERE usuario = ?");
    $st->bind_param("s", $clave);
    $st->execute();
    $st->close();
}

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $usuario  = trim($_POST["usuario"] ?? "");
    $password = $_POST["password"] ?? "";
    $clave    = mb_strtolower(mb_substr($usuario, 0, 50, "UTF-8"), "UTF-8");

    $captchaOk = captcha_validar("login", $_POST["captcha"] ?? "");
    $minBloqueo = minutosRestantes($conexion, $clave);

    if ($minBloqueo > 0) {

        $mensaje = "Cuenta bloqueada por demasiados intentos fallidos. "
                 . "Inténtalo de nuevo en $minBloqueo minuto(s).";

    } elseif (!$captchaOk) {

        $mensaje = "El código de verificación es incorrecto o expiró. "
                 . "Inténtalo con el nuevo código.";

    } else {

        $stmt = $conexion->prepare(
            "SELECT id, usuario, password FROM usuarios WHERE usuario = ?"
        );
        $stmt->bind_param("s", $usuario);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $datos = $resultado->num_rows === 1 ? $resultado->fetch_assoc() : null;
        $stmt->close();

        if ($datos && password_verify($password, $datos["password"])) {

            limpiarIntentos($conexion, $clave);
            session_regenerate_id(true);
            $_SESSION["admin"] = $datos["usuario"];

            header("Location: panelAdmin.php");
            exit();

        }

        $fallos = registrarFallo($conexion, $clave);
        $minBloqueo = minutosRestantes($conexion, $clave);

        if ($minBloqueo > 0) {
            $mensaje = "Superaste los " . MAX_INTENTOS . " intentos permitidos. "
                     . "La cuenta quedó bloqueada $minBloqueo minuto(s).";
        } else {
            $restan = MAX_INTENTOS - $fallos;
            $mensaje = "Usuario o contraseña incorrectos. "
                     . "Te quedan $restan intento(s).";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="style.css">

    <title>Acceso | CETis 120</title>

    <link rel="icon" href="imagenes/icono.ico" type="image/x-icon">

</head>


<body class="pagina-login">


    <main class="login-container">


        <div class="login-box">


            <img 
                src="imagenes/logo.png" 
                alt="Logo CETis 120"
                class="login-logo"
            >


            <h1>
                Panel de Administración
            </h1>


            <p class="login-subtitulo">
                Proceso de Titulación
            </p>


            <?php if ($mensaje != ""): ?>

                <p class="mensaje-error">
                    <?php echo $mensaje; ?>
                </p>

            <?php endif; ?>


            <form method="POST">


                <div class="campo">

                    <label for="usuario">
                        Usuario
                    </label>

                    <input 
                        type="text"
                        id="usuario"
                        name="usuario"
                        placeholder="Ingresa tu usuario"
                        required
                    >

                </div>


                <div class="campo">

                    <label for="password">
                        Contraseña
                    </label>

                    <input 
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Ingresa tu contraseña"
                        required
                    >

                </div>


                <?php captcha_campo("login"); ?>


                <button type="submit" class="boton-login">
                    INICIAR SESIÓN
                </button>


            </form>


            <a href="principal.php" class="volver">
                ← Volver al inicio
            </a>


        </div>


    </main>


</body>

</html>