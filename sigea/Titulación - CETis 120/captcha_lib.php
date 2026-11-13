<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

const CAPTCHA_CARACTERES = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789"; // sin I, O, 0, 1
const CAPTCHA_LONGITUD   = 2;
const CAPTCHA_VIDA_SEG   = 900; // 5 minutos

function captcha_generar(string $formulario): string
{
    $codigo = "";
    $max = strlen(CAPTCHA_CARACTERES) - 1;

    for ($i = 0; $i < CAPTCHA_LONGITUD; $i++) {
        $codigo .= CAPTCHA_CARACTERES[random_int(0, $max)];
    }

    $_SESSION["captcha"][$formulario] = [
        "codigo" => $codigo,
        "expira" => time() + CAPTCHA_VIDA_SEG,
    ];

    return $codigo;
}


/* Un captcha solo sirve UNA vez (se borra al validar). */
function captcha_validar(string $formulario, $entrada): bool
{
    if (!isset($_SESSION["captcha"]) || !is_array($_SESSION["captcha"])) {
        return false;
    }

    $guardado = $_SESSION["captcha"][$formulario] ?? null;
    
    unset($_SESSION["captcha"][$formulario]);

    if (!$guardado || time() > $guardado["expira"]) {
        return false;
    }

    $entrada = strtoupper(trim((string) $entrada));

    return $entrada !== "" && hash_equals($guardado["codigo"], $entrada);
}