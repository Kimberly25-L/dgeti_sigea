# SIGEA · Sistema Académico DGETI

Tecnologías: HTML, CSS, JavaScript (frontend) · PHP + MySQL (backend y base de datos).

## Estructura de carpetas
```
htdocs/sigea/
├── menu.html                 (menú principal de SIGEA)
├── gestor_dgeti/             (este módulo)
│   ├── index.html, login.html, registro.html
│   ├── portaladmin.html, portaladminbot.html, portalalumno.html, portalmaestro.html
│   ├── api/api.php
│   ├── css/style.css
│   ├── js/ (app.js, auth.js, admin.js, adminbot.js, alumno.js, maestro.js, bot.js)
│   ├── img/ (logo.jpg, mascota.jpg)
│   └── sql/sistema_dgeti.sql
└── Titulación - CETis 120/
```

## Cómo ejecutarlo (XAMPP)
1. Copia la carpeta `sigea` dentro de `C:\xampp\htdocs\`.
2. Abre XAMPP y enciende **Apache** y **MySQL**.
3. En http://localhost/phpmyadmin → Importar → elige `gestor_dgeti/sql/sistema_dgeti.sql`.
4. Abre http://localhost/sigea/menu.html (o directo http://localhost/sigea/gestor_dgeti/index.html).
   Si MySQL tiene contraseña, cámbiala en `api/api.php`, constante `DB_PASS`.
5. No abras los archivos con doble clic (file://): el login y la base de datos solo funcionan a través de Apache.

## Cuentas iniciales (cámbialas)
| Rol | Usuario | Contraseña |
|---|---|---|
| Administrador | ADMIN001 | Admin2026! |
| Admin del asistente | BOT001 | Bot2026! |

## Flujo
1. El **administrador** da de alta alumnos (n.° de control) y maestros (RFC), grupos y materias.
2. Alumno/maestro entra a **Activar cuenta**, escribe su identificador + nombre exacto y crea contraseña.
   Si no fue dado de alta, el sistema lo rechaza.
3. El **maestro** captura calificaciones y reportes; el **alumno** solo los consulta.
4. El **admin del asistente** alimenta la tabla `conocimiento_bot`; **Jaguares** responde con ideas de proyecto.

Datos de prueba: alumno 220502 (Ana Valeria Mendoza), maestro GOMA800101ABC (Alejandro Gómez).

## Seguridad
- Captcha de imagen en login y registro (vence a los 5 min y sirve una sola vez).
- 8 contraseñas incorrectas seguidas = bloqueo de 15 minutos por usuario (tabla `login_intentos`, se crea sola).
  Para desbloquear en pruebas: `DELETE FROM login_intentos;` en phpMyAdmin.

## Autores
Edita los nombres completos en `js/app.js` (constante `AUTORES`).
