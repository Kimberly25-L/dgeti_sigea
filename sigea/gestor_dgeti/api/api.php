<?php
/* =========================================================
   API ÚNICA DEL SISTEMA DGETI  (PHP + MySQL)
   Uso: api/api.php?action=NOMBRE
   ========================================================= */
session_start();

const DB_HOST = 'sql313.infinityfree.com';
const DB_NAME = 'if0_43367189_db_dgti';
const DB_USER = 'if0_43367189';
const DB_PASS = 'B8F8gAOeYEk';          // XAMPP trae root sin contraseña
const PERIODO = '2026-2';    // periodo escolar actual

function db() {
    static $p;
    if (!$p) {
        $p = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
             PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    }
    return $p;
}
function q($sql, $p = []) { $s = db()->prepare($sql); $s->execute($p); return $s; }
function out($d, $c = 200) {
    http_response_code($c);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($d, JSON_UNESCAPED_UNICODE);
    exit;
}
function err($m, $c = 400) { out(['ok' => false, 'error' => $m], $c); }
function body() {
    static $b;
    if ($b === null) $b = json_decode(file_get_contents('php://input'), true) ?: [];
    return $b;
}
function need($roles) {
    $u = $_SESSION['u'] ?? null;
    if (!$u || !in_array($u['rol'], (array)$roles, true)) err('Sesión no válida o sin permiso.', 401);
    return $u;
}
function req($arr, $campos) {
    foreach ($campos as $c) if (!isset($arr[$c]) || trim((string)$arr[$c]) === '') err("Falta el campo: $c");
}

/* ---------------- CAPTCHA (imagen SVG, sin librerías) ---------------- */
function captcha_svg() {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $code = '';
    for ($i = 0; $i < 5; $i++) $code .= $chars[random_int(0, strlen($chars) - 1)];
    $_SESSION['captcha'] = $code;
    $_SESSION['captcha_exp'] = time() + 300;   // vence en 5 minutos
    $col = ['#b32424', '#08472e', '#8a5a00', '#104587'];
    $s = '<svg xmlns="http://www.w3.org/2000/svg" width="170" height="56" viewBox="0 0 170 56"><rect width="170" height="56" rx="8" fill="#f5efe6"/>';
    for ($i = 0; $i < 6; $i++)
        $s .= sprintf('<line x1="%d" y1="%d" x2="%d" y2="%d" stroke="#94a3b8" stroke-width="1.5" opacity=".6"/>',
            random_int(0, 170), random_int(0, 56), random_int(0, 170), random_int(0, 56));
    for ($i = 0; $i < 5; $i++)
        $s .= sprintf('<text x="%d" y="%d" font-size="30" font-weight="800" font-family="monospace" fill="%s" transform="rotate(%d %d 35)">%s</text>',
            15 + $i * 29, random_int(34, 42), $col[array_rand($col)], random_int(-22, 22), 15 + $i * 29, $code[$i]);
    for ($i = 0; $i < 30; $i++)
        $s .= sprintf('<circle cx="%d" cy="%d" r="1.2" fill="#94a3b8" opacity=".6"/>', random_int(0, 170), random_int(0, 56));
    header('Content-Type: image/svg+xml');
    header('Cache-Control: no-store');
    echo $s . '</svg>';
    exit;
}
function check_captcha($v) {
    $ok = isset($_SESSION['captcha'], $_SESSION['captcha_exp'])
          && time() <= $_SESSION['captcha_exp']
          && hash_equals($_SESSION['captcha'], strtoupper(trim((string)$v)));
    unset($_SESSION['captcha'], $_SESSION['captcha_exp']);   // un código sirve una sola vez
    if (!$ok) err('Captcha incorrecto. Intenta con el código nuevo.');
}


/* ---------------- BLOQUEO POR INTENTOS FALLIDOS ----------------
   8 contraseñas incorrectas seguidas = bloqueo de 15 minutos
   (por rol + usuario). La tabla se crea sola la primera vez.   */
const MAX_INTENTOS = 2;
const MIN_BLOQUEO  = 15;

function clave_login($rol, $ident) {
    return mb_substr(mb_strtolower($rol . '|' . trim($ident), 'UTF-8'), 0, 80);
}
function minutos_bloqueo($clave) {
    q("CREATE TABLE IF NOT EXISTS login_intentos (
          clave VARCHAR(80) NOT NULL PRIMARY KEY,
          intentos INT NOT NULL DEFAULT 0,
          bloqueado_hasta DATETIME NULL,
          ultimo DATETIME NULL
       ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $m = q("SELECT CEIL(TIMESTAMPDIFF(SECOND, NOW(), bloqueado_hasta) / 60)
            FROM login_intentos WHERE clave=? AND bloqueado_hasta > NOW()", [$clave])->fetchColumn();
    return $m ? max(1, (int)$m) : 0;
}
function registrar_fallo($clave) {
    q("INSERT INTO login_intentos (clave, intentos, ultimo) VALUES (?, 1, NOW())
       ON DUPLICATE KEY UPDATE intentos = intentos + 1, ultimo = NOW()", [$clave]);
    $n = (int)q("SELECT intentos FROM login_intentos WHERE clave=?", [$clave])->fetchColumn();
    if ($n >= MAX_INTENTOS) {
        q("UPDATE login_intentos SET intentos=0, bloqueado_hasta=DATE_ADD(NOW(), INTERVAL " . (int)MIN_BLOQUEO . " MINUTE) WHERE clave=?", [$clave]);
    }
    return $n;
}

/* ---------------- ASISTENTE (búsqueda por palabras clave) ---------------- */
function norm($s) {
    $s = mb_strtolower($s, 'UTF-8');
    $s = strtr($s, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n']);
    return preg_replace('/[^a-z0-9 ]+/', ' ', $s);
}
function tokens($s) {
    $stop = ['para','con','una','uno','unos','unas','que','los','las','del','por','mas','como','quiero','dame','dime','ideas','idea','proyecto','proyectos','algun','alguna','sobre','tengo','hacer','necesito','puedo','algo'];
    return array_values(array_filter(array_unique(explode(' ', norm($s))),
        fn($t) => strlen($t) >= 3 && !in_array($t, $stop, true)));
}
function bot_responder($msg, $especialidad) {
    $tk = tokens($msg);
    if (!$tk) {
        if (preg_match('/hola|buen|hey|saludos/', norm($msg)))
            return ['respuesta' => '¡Hola! Somos Jaguares 🐾, tus asistentes de proyectos. Dime qué área te gusta (programación, electrónica, diseño…) y te damos ideas.', 'ideas' => []];
        return ['respuesta' => 'Cuéntame un poco más: ¿de qué área quieres tu proyecto?', 'ideas' => []];
    }
    $rows = q("SELECT tema, contenido, pregunta_clave, palabras_clave FROM conocimiento_bot WHERE activo=1")->fetchAll();
    $esp = $especialidad ? tokens($especialidad) : [];
    $res = [];
    foreach ($rows as $r) {
        $kw = norm($r['palabras_clave'] ?? ''); $tm = norm($r['tema']);
        $pg = norm($r['pregunta_clave']); $ct = norm($r['contenido']);
        $sc = 0;
        foreach ($tk as $t) {
            if (strpos($kw, $t) !== false) $sc += 3;
            if (strpos($tm, $t) !== false) $sc += 2;
            if (strpos($pg, $t) !== false) $sc += 2;
            if (strpos($ct, $t) !== false) $sc += 1;
        }
        if ($sc > 0) foreach ($esp as $e) if (strpos($kw . $tm, $e) !== false) $sc += 1;
        if ($sc > 0) $res[] = ['score' => $sc, 'tema' => $r['tema'], 'contenido' => $r['contenido']];
    }
    usort($res, fn($a, $b) => $b['score'] <=> $a['score']);
    $res = array_slice($res, 0, 3);
    if (!$res) {
        $temas = array_column($rows, 'tema');
        return ['respuesta' => 'Aún no tengo ideas sobre eso. Prueba con alguno de estos temas: ' . implode(', ', $temas) . '.', 'ideas' => []];
    }
    return ['respuesta' => '¡Mira lo que encontré para ti! 💡',
            'ideas' => array_map(fn($r) => ['tema' => $r['tema'], 'contenido' => $r['contenido']], $res)];
}

/* ---------------- ENRUTADOR ---------------- */
$action = $_GET['action'] ?? '';
if ($action === 'captcha') captcha_svg();

try {
    switch ($action) {

    /* ===== SESIÓN ===== */
    case 'login':
        $b = body();
        req($b, ['rol', 'identificador', 'password']);
        $clave = clave_login($b['rol'], $b['identificador']);
        $min = minutos_bloqueo($clave);
        if ($min > 0) err("Acceso bloqueado por demasiados intentos fallidos. Intenta de nuevo en $min minuto(s).", 429);
        check_captcha($b['captcha'] ?? '');   // un captcha incorrecto no cuenta como intento
        $u = q("SELECT * FROM usuarios WHERE rol=? AND identificador=? AND activo=1",
               [$b['rol'], trim($b['identificador'])])->fetch();
        if (!$u || !password_verify($b['password'], $u['password_hash'])) {
            $n = registrar_fallo($clave);
            $min = minutos_bloqueo($clave);
            if ($min > 0) err('Superaste ' . MAX_INTENTOS . " intentos. Acceso bloqueado $min minuto(s).", 429);
            err('Credenciales incorrectas o usuario no dado de alta. Te quedan ' . (MAX_INTENTOS - $n) . ' intento(s).', 401);
        }
        q("DELETE FROM login_intentos WHERE clave=?", [$clave]);
        session_regenerate_id(true);
        $_SESSION['u'] = ['id' => (int)$u['id'], 'nombre' => $u['nombre'], 'rol' => $u['rol'], 'ref' => $u['ref_id']];
        out(['ok' => true, 'rol' => $u['rol']]);

    case 'logout':
        $_SESSION = []; session_destroy(); out(['ok' => true]);

    case 'me':
        $u = need(['admin', 'admin_bot', 'alumno', 'maestro']);
        out(['ok' => true, 'u' => $u]);

    /* ===== REGISTRO: solo si el admin lo dio de alta ===== */
    case 'register':
        $b = body(); check_captcha($b['captcha'] ?? '');
        req($b, ['rol', 'identificador', 'nombre', 'password']);
        if (strlen($b['password']) < 8) err('La contraseña debe tener al menos 8 caracteres.');
        if ($b['rol'] === 'alumno')       { $t = 'alumnos_autorizados';  $c = 'numero_control'; $id = trim($b['identificador']); }
        elseif ($b['rol'] === 'maestro')  { $t = 'maestros_autorizados'; $c = 'rfc'; $id = strtoupper(trim($b['identificador'])); }
        else err('Rol no válido.');
        $a = q("SELECT * FROM $t WHERE $c=?", [$id])->fetch();
        if (!$a) err('No estás dado de alta. Pide al administrador que te registre.', 403);
        if ($a['registrado']) err('Esta cuenta ya fue activada. Inicia sesión.');
        if (mb_strtolower(trim($b['nombre']), 'UTF-8') !== mb_strtolower($a['nombre'], 'UTF-8'))
            err('El nombre no coincide con el registrado por el administrador.');
        $correo = trim($b['correo'] ?? '') ?: null;
        db()->beginTransaction();
        q("INSERT INTO usuarios (nombre, identificador, correo, password_hash, rol, ref_id) VALUES (?,?,?,?,?,?)",
          [$a['nombre'], $id, $correo, password_hash($b['password'], PASSWORD_DEFAULT), $b['rol'], $a['id']]);
        q("UPDATE $t SET registrado=1 WHERE id=?", [$a['id']]);
        db()->commit();
        out(['ok' => true]);

    /* ===== ADMINISTRADOR ===== */
    case 'admin_list':
        need('admin');
        $sql = [
            'alumno'  => "SELECT id, numero_control, nombre, grupo, especialidad, registrado FROM alumnos_autorizados ORDER BY id DESC",
            'maestro' => "SELECT id, rfc, nombre, especialidad, registrado FROM maestros_autorizados ORDER BY id DESC",
            'grupo'   => "SELECT id, nombre, especialidad, semestre FROM grupos ORDER BY nombre",
            'materia' => "SELECT id, nombre FROM materias ORDER BY nombre",
        ][$_GET['tipo'] ?? ''] ?? err('Tipo no válido.');
        out(['ok' => true, 'datos' => q($sql)->fetchAll()]);

    case 'admin_add':
        $u = need('admin'); $b = body(); $d = $b['datos'] ?? [];
        switch ($b['tipo'] ?? '') {
            case 'alumno':
                req($d, ['numero_control', 'nombre']);
                q("INSERT INTO alumnos_autorizados (numero_control, nombre, grupo, especialidad, creado_por) VALUES (?,?,?,?,?)",
                  [trim($d['numero_control']), trim($d['nombre']), trim($d['grupo'] ?? '') ?: null, trim($d['especialidad'] ?? '') ?: null, $u['id']]);
                break;
            case 'maestro':
                req($d, ['rfc', 'nombre']);
                $rfc = strtoupper(trim($d['rfc']));
                if (!preg_match('/^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$/u', $rfc)) err('RFC con formato no válido.');
                q("INSERT INTO maestros_autorizados (rfc, nombre, especialidad, creado_por) VALUES (?,?,?,?)",
                  [$rfc, trim($d['nombre']), trim($d['especialidad'] ?? '') ?: null, $u['id']]);
                break;
            case 'grupo':
                req($d, ['nombre']);
                q("INSERT INTO grupos (nombre, especialidad, semestre) VALUES (?,?,?)",
                  [trim($d['nombre']), trim($d['especialidad'] ?? '') ?: null, ($d['semestre'] ?? '') !== '' ? (int)$d['semestre'] : null]);
                break;
            case 'materia':
                req($d, ['nombre']);
                q("INSERT INTO materias (nombre) VALUES (?)", [trim($d['nombre'])]);
                break;
            default: err('Tipo no válido.');
        }
        out(['ok' => true]);

    case 'admin_delete':
        need('admin'); $b = body(); $id = (int)($b['id'] ?? 0);
        db()->beginTransaction();
        switch ($b['tipo'] ?? '') {
            case 'alumno':  q("DELETE FROM usuarios WHERE rol='alumno' AND ref_id=?", [$id]);  q("DELETE FROM calificaciones WHERE alumno_id=?", [$id]); q("DELETE FROM reportes_conducta WHERE alumno_id=?", [$id]); q("DELETE FROM alumnos_autorizados WHERE id=?", [$id]); break;
            case 'maestro': q("DELETE FROM usuarios WHERE rol='maestro' AND ref_id=?", [$id]); q("DELETE FROM maestros_autorizados WHERE id=?", [$id]); break;
            case 'grupo':   q("DELETE FROM grupos WHERE id=?", [$id]); break;
            case 'materia': q("DELETE FROM materias WHERE id=?", [$id]); break;
            default: db()->rollBack(); err('Tipo no válido.');
        }
        db()->commit();
        out(['ok' => true]);

    /* ===== MAESTRO ===== */
    case 'm_contexto':
        $u = need('maestro');
        out(['ok' => true, 'periodo' => PERIODO, 'nombre' => $u['nombre'],
             'especialidad' => q("SELECT especialidad FROM maestros_autorizados WHERE id=?", [$u['ref']])->fetchColumn(),
             'grupos' => q("SELECT nombre FROM grupos ORDER BY nombre")->fetchAll(PDO::FETCH_COLUMN),
             'materias' => q("SELECT id, nombre FROM materias ORDER BY nombre")->fetchAll()]);

    case 'm_alumnos':
        need('maestro');
        $rows = q("SELECT a.id, a.numero_control, a.nombre, c.calificacion,
                          (SELECT COUNT(*) FROM reportes_conducta r WHERE r.alumno_id=a.id) AS reportes
                   FROM alumnos_autorizados a
                   LEFT JOIN calificaciones c ON c.alumno_id=a.id AND c.materia_id=? AND c.periodo=?
                   WHERE a.grupo=? ORDER BY a.nombre",
                  [(int)($_GET['materia'] ?? 0), PERIODO, $_GET['grupo'] ?? ''])->fetchAll();
        out(['ok' => true, 'alumnos' => $rows]);

    case 'm_guardar':
        $u = need('maestro'); $b = body(); $n = 0;
        $mat = (int)($b['materia'] ?? 0);
        if (!$mat) err('Selecciona una materia.');
        foreach (($b['items'] ?? []) as $it) {
            if (!isset($it['calificacion']) || $it['calificacion'] === '' || $it['calificacion'] === null) continue;
            $cal = (float)$it['calificacion'];
            if ($cal < 0 || $cal > 10) err('Las calificaciones deben estar entre 0 y 10.');
            q("INSERT INTO calificaciones (alumno_id, maestro_id, materia_id, periodo, calificacion) VALUES (?,?,?,?,?)
               ON DUPLICATE KEY UPDATE calificacion=VALUES(calificacion), maestro_id=VALUES(maestro_id)",
              [(int)$it['alumno_id'], $u['ref'], $mat, PERIODO, $cal]);
            $n++;
        }
        out(['ok' => true, 'guardados' => $n]);

    case 'm_reporte':
        $u = need('maestro'); $b = body();
        req($b, ['alumno_id', 'descripcion']);
        $tipo = in_array($b['tipo'] ?? '', ['indisciplina', 'retardo', 'otro'], true) ? $b['tipo'] : 'otro';
        q("INSERT INTO reportes_conducta (alumno_id, maestro_id, tipo, descripcion) VALUES (?,?,?,?)",
          [(int)$b['alumno_id'], $u['ref'], $tipo, trim($b['descripcion'])]);
        out(['ok' => true]);

    /* ===== ALUMNO ===== */
    case 'a_resumen':
        $u = need('alumno');
        $al = q("SELECT numero_control, nombre, grupo, especialidad FROM alumnos_autorizados WHERE id=?", [$u['ref']])->fetch();
        $cal = q("SELECT m.nombre AS materia, ma.nombre AS docente, c.periodo, c.calificacion
                  FROM calificaciones c JOIN materias m ON m.id=c.materia_id
                  JOIN maestros_autorizados ma ON ma.id=c.maestro_id
                  WHERE c.alumno_id=? ORDER BY c.periodo DESC, m.nombre", [$u['ref']])->fetchAll();
        $rep = q("SELECT r.tipo, r.descripcion, r.fecha, ma.nombre AS docente
                  FROM reportes_conducta r JOIN maestros_autorizados ma ON ma.id=r.maestro_id
                  WHERE r.alumno_id=? ORDER BY r.fecha DESC", [$u['ref']])->fetchAll();
        out(['ok' => true, 'alumno' => $al, 'calificaciones' => $cal, 'reportes' => $rep]);

    /* ===== ASISTENTE ===== */
    case 'bot_chat':
        $u = need(['alumno', 'admin_bot']); $b = body();
        $esp = $u['rol'] === 'alumno'
            ? q("SELECT especialidad FROM alumnos_autorizados WHERE id=?", [$u['ref']])->fetchColumn() : null;
        out(['ok' => true] + bot_responder(mb_substr((string)($b['mensaje'] ?? ''), 0, 300), $esp));

    case 'bot_list':
        need('admin_bot');
        out(['ok' => true, 'datos' => q("SELECT id, tema, pregunta_clave, contenido, palabras_clave, activo FROM conocimiento_bot ORDER BY id DESC")->fetchAll()]);

    case 'bot_save':
        $u = need('admin_bot'); $b = body();
        req($b, ['tema', 'pregunta_clave', 'contenido']);
        $v = [trim($b['tema']), trim($b['pregunta_clave']), trim($b['contenido']), trim($b['palabras_clave'] ?? ''), empty($b['activo']) ? 0 : 1];
        if (!empty($b['id']))
            q("UPDATE conocimiento_bot SET tema=?, pregunta_clave=?, contenido=?, palabras_clave=?, activo=? WHERE id=?", [...$v, (int)$b['id']]);
        else
            q("INSERT INTO conocimiento_bot (tema, pregunta_clave, contenido, palabras_clave, activo, creado_por) VALUES (?,?,?,?,?,?)", [...$v, $u['id']]);
        out(['ok' => true]);

    case 'bot_delete':
        need('admin_bot');
        q("DELETE FROM conocimiento_bot WHERE id=?", [(int)(body()['id'] ?? 0)]);
        out(['ok' => true]);

    default:
        err('Acción no encontrada.', 404);
    }
} catch (PDOException $e) {
    if (db()->inTransaction()) db()->rollBack();
    if ($e->getCode() === '23000') err('Ese registro ya existe o tiene datos relacionados (calificaciones, reportes).');
    err('Error de base de datos. Revisa la conexión en api/api.php.', 500);
}
