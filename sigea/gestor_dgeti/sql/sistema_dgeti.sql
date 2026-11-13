-- =====================================================
--  SISTEMA DGETI · Base de datos
--  Importar en phpMyAdmin (XAMPP) o con: mysql -u root < sistema_dgeti.sql
-- =====================================================


-- Usuarios con cuenta activa. ref_id apunta al registro autorizado
-- (alumnos_autorizados.id o maestros_autorizados.id) según el rol.
CREATE TABLE usuarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    identificador VARCHAR(20) NOT NULL,
    correo VARCHAR(150) NULL,
    password_hash VARCHAR(255) NOT NULL,
    rol ENUM('admin','admin_bot','alumno','maestro') NOT NULL,
    ref_id INT UNSIGNED NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    fecha_registro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_usuario_rol_identificador (rol, identificador),
    UNIQUE KEY uk_usuario_correo (correo)
) ENGINE=InnoDB;

-- Lista blanca: solo quien esté aquí puede crear su cuenta.
CREATE TABLE alumnos_autorizados (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    numero_control VARCHAR(20) NOT NULL UNIQUE,
    nombre VARCHAR(150) NOT NULL,
    grupo VARCHAR(20) NULL,
    especialidad VARCHAR(100) NULL,
    registrado TINYINT(1) NOT NULL DEFAULT 0,
    creado_por INT UNSIGNED NULL,
    fecha_alta TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_alumno_admin FOREIGN KEY (creado_por)
        REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE maestros_autorizados (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rfc VARCHAR(13) NOT NULL UNIQUE,
    nombre VARCHAR(150) NOT NULL,
    especialidad VARCHAR(100) NULL,
    registrado TINYINT(1) NOT NULL DEFAULT 0,
    creado_por INT UNSIGNED NULL,
    fecha_alta TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_maestro_admin FOREIGN KEY (creado_por)
        REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE materias (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE grupos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(30) NOT NULL,
    especialidad VARCHAR(100) NULL,
    semestre TINYINT UNSIGNED NULL,
    UNIQUE KEY uk_grupo (nombre, especialidad)
) ENGINE=InnoDB;

-- alumno_id / maestro_id apuntan a las tablas autorizadas
-- (así el maestro puede calificar aunque el alumno aún no active su cuenta).
CREATE TABLE calificaciones (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    alumno_id INT UNSIGNED NOT NULL,
    maestro_id INT UNSIGNED NOT NULL,
    materia_id INT UNSIGNED NOT NULL,
    periodo VARCHAR(30) NOT NULL,
    calificacion DECIMAL(4,2) NOT NULL,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_calificacion CHECK (calificacion >= 0 AND calificacion <= 10),
    CONSTRAINT fk_calif_alumno  FOREIGN KEY (alumno_id)  REFERENCES alumnos_autorizados(id),
    CONSTRAINT fk_calif_maestro FOREIGN KEY (maestro_id) REFERENCES maestros_autorizados(id),
    CONSTRAINT fk_calif_materia FOREIGN KEY (materia_id) REFERENCES materias(id),
    UNIQUE KEY uk_calificacion (alumno_id, materia_id, periodo)
) ENGINE=InnoDB;

CREATE TABLE reportes_conducta (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    alumno_id INT UNSIGNED NOT NULL,
    maestro_id INT UNSIGNED NOT NULL,
    tipo ENUM('indisciplina','retardo','otro') NOT NULL DEFAULT 'otro',
    descripcion TEXT NOT NULL,
    fecha TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_rep_alumno  FOREIGN KEY (alumno_id)  REFERENCES alumnos_autorizados(id),
    CONSTRAINT fk_rep_maestro FOREIGN KEY (maestro_id) REFERENCES maestros_autorizados(id)
) ENGINE=InnoDB;

-- Base de conocimiento del asistente (la alimenta el admin del bot).
CREATE TABLE conocimiento_bot (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tema VARCHAR(120) NOT NULL,
    pregunta_clave VARCHAR(255) NOT NULL,
    contenido TEXT NOT NULL,
    palabras_clave VARCHAR(500) DEFAULT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    creado_por INT UNSIGNED NULL,
    fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_conocimiento_admin FOREIGN KEY (creado_por)
        REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_conocimiento_tema (tema),
    INDEX idx_conocimiento_activo (activo)
) ENGINE=InnoDB;

-- Control de intentos de acceso (8 fallos = bloqueo de 15 min).
-- api.php también la crea sola si no existe.
CREATE TABLE IF NOT EXISTS login_intentos (
    clave VARCHAR(80) NOT NULL PRIMARY KEY,
    intentos INT NOT NULL DEFAULT 0,
    bloqueado_hasta DATETIME NULL,
    ultimo DATETIME NULL
) ENGINE=InnoDB;

-- ================= DATOS INICIALES =================
-- Cuentas iniciales (CAMBIAR contraseñas antes de usar en serio):
--   admin     -> usuario ADMIN001 / contraseña Admin2026!
--   admin_bot -> usuario BOT001   / contraseña Bot2026!
INSERT INTO usuarios (nombre, identificador, password_hash, rol) VALUES
('Administrador General', 'ADMIN001', '$2y$10$fWZSMdBTz678MbZrJ0k.3.sUlZj4PCyhrf0x5dKPonfs15fA6f4UW', 'admin'),
('Administrador del Asistente', 'BOT001', '$2y$10$PYe2Oha7YDJRWVjqfkti..OGUL.v1iGttFJjY8g6GfKXKlsEPq40S', 'admin_bot');

INSERT INTO materias (nombre) VALUES
('Español'),('Matemáticas'),('Ciencias'),('Programación'),('Inglés'),('Ética profesional');

INSERT INTO grupos (nombre, especialidad, semestre) VALUES
('5°A','Programación',5),('3°A','Programación',3),('1°D','Programación',1);

-- Alumnos y maestro de prueba (aún sin cuenta: deben registrarse).
INSERT INTO alumnos_autorizados (numero_control, nombre, grupo, especialidad, creado_por) VALUES
('220501','Carlos Eduardo Pérez','5°A','Programación',1),
('220502','Ana Valeria Mendoza','5°A','Programación',1),
('220503','Diana Laura Delgado','5°A','Programación',1);
INSERT INTO maestros_autorizados (rfc, nombre, especialidad, creado_por) VALUES
('GOMA800101ABC','Alejandro Gómez','Programación',1);

-- Conocimiento inicial del asistente (ideas de proyectos).
INSERT INTO conocimiento_bot (tema, pregunta_clave, contenido, palabras_clave, creado_por) VALUES
('Programación web','Ideas de proyectos de programación web',
 'Sistema de inventario para una papelería con login y reportes; página web de una tienda local con carrito de compras; bolsa de trabajo para egresados de tu plantel; agenda escolar con recordatorios.',
 'programacion,web,pagina,sitio,html,css,javascript,php,sistema,tienda,login',1),
('Aplicaciones móviles','Ideas de proyectos de apps móviles',
 'App para consultar horarios y avisos del plantel; app de rutas de transporte local; app de reciclaje que indique centros de acopio cercanos; lista de tareas con alarmas para estudiantes.',
 'app,aplicacion,movil,celular,android,programacion,telefono',1),
('Base de datos','Ideas de proyectos con bases de datos',
 'Control de préstamo de libros de la biblioteca; registro de asistencia por número de control; sistema de citas para el departamento de orientación; catálogo de proyectos de generaciones anteriores.',
 'base,datos,mysql,sql,registro,control,biblioteca,asistencia,sistema',1),
('Electrónica y robótica','Ideas de proyectos de electrónica',
 'Riego automático con Arduino y sensor de humedad; alarma de seguridad con sensor de movimiento; brazo robótico educativo; estación meteorológica casera con pantalla LCD.',
 'electronica,arduino,robotica,sensor,circuito,robot,automatico,iot',1),
('Mecatrónica y automatización','Ideas de proyectos de mecatrónica',
 'Banda transportadora clasificadora de objetos por color; puerta automática con sensor; maqueta de semáforo inteligente; dispensador automático de gel.',
 'mecatronica,automatizacion,banda,motor,plc,maqueta,semaforo',1),
('Contabilidad y administración','Ideas de proyectos administrativos',
 'Hoja de cálculo de control de gastos para una microempresa; plan de negocio de la cooperativa escolar; sistema de facturación sencillo; análisis financiero de una tiendita.',
 'contabilidad,administracion,negocio,empresa,finanzas,gastos,excel,facturacion',1),
('Ofimática y diseño','Ideas de proyectos de diseño y ofimática',
 'Identidad gráfica (logo y papelería) para un negocio local; revista o periódico mural digital del plantel; presentación interactiva de orientación vocacional; tutoriales en video de herramientas de oficina.',
 'ofimatica,diseno,grafico,logo,video,presentacion,word,excel,powerpoint,revista',1),
('Medio ambiente','Ideas de proyectos ambientales',
 'Compostero escolar; sistema de captación de agua de lluvia; medidor de calidad del aire con sensores; campaña digital de reciclaje.',
 'ambiente,reciclaje,agua,ecologia,sustentable,aire,composta',1),
('Proyecto fácil y rápido','Ideas sencillas para empezar',
 'Calculadora de promedios escolares; generador de horarios; juego de preguntas sobre tu especialidad; página personal con tu portafolio.',
 'facil,sencillo,rapido,basico,principiante,simple,empezar,corto,juego,portafolio',1),
('Inteligencia artificial','Ideas de proyectos con inteligencia artificial',
 'Chatbot de preguntas frecuentes del plantel; clasificador de imágenes de plantas; recomendador de libros; detector de spam en mensajes.',
 'inteligencia,artificial,ia,chatbot,bot,aprendizaje,datos,imagenes',1);
