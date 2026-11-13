<?php
function captcha_campo(string $formulario): void
{
    $f = htmlspecialchars($formulario, ENT_QUOTES, "UTF-8");
    ?>
    <div class="captcha-bloque">
        <label for="captcha-<?= $f ?>">Captcha de seguridad</label>

        <div class="captcha-box">
            <img
                id="captcha-img-<?= $f ?>"
                src="captcha.php?f=<?= $f ?>&amp;t=<?= time() ?>"
                alt="Captcha"
                width="170" height="56"
            >
            <button
                type="button"
                class="btn-refrescar"
                title="Nuevo código"
                aria-label="Nuevo código"
                onclick="recargarCaptcha('<?= $f ?>')"
            >🔄</button>
        </div>

        <input
            type="text"
            id="captcha-<?= $f ?>"
            name="captcha"
            class="captcha-input"
            placeholder="Escribe los 5 caracteres"
            maxlength="5"
            autocomplete="off"
            autocapitalize="characters"
            required
        >
    </div>

    <?php if (!defined("CAPTCHA_ASSETS")): define("CAPTCHA_ASSETS", true); ?>
    <style>
        .captcha-bloque { margin: 14px 0 16px; text-align: left; }
        .captcha-bloque label { display:block; font-weight:bold; font-size:15px; color:#0A2B5C; margin-bottom:7px; }
        .captcha-box { display:flex; align-items:center; gap:10px; margin-bottom:12px; }
        .captcha-box img { border:1px solid #cddbd5; border-radius:8px; max-width:100%; height:auto; }
        .btn-refrescar { background:#eaf6f0; border:1px solid #dce6e1; border-radius:8px; padding:10px 12px; cursor:pointer; font-size:1rem; transition:transform .4s; }
        .btn-refrescar:hover { transform:rotate(180deg); }
        .captcha-input { width:100%; height:46px; padding:0 15px; border:1px solid #D0D5DA; border-radius:8px; font-size:15px; text-transform:uppercase; letter-spacing:3px; outline:none; box-sizing:border-box; }
        .captcha-input::placeholder { text-transform:none; letter-spacing:0; }
        .captcha-input:focus { border-color:#0A2B5C; }
    </style>
    <script>
        function recargarCaptcha(f) {
            var img = document.getElementById("captcha-img-" + f);
            if (img) img.src = "captcha.php?f=" + f + "&t=" + Date.now();
            var campo = document.getElementById("captcha-" + f);
            if (campo) { campo.value = ""; campo.focus(); }
        }
    </script>
    <?php endif;
}
