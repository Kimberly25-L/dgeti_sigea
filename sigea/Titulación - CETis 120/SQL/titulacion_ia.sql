-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 22-10-2026 a las 21:23:51
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `titulacion_ia`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id`, `nombre`, `descripcion`, `activo`) VALUES
(1, 'Promedio', 'Información sobre la modalidad de titulación por promedio', 1),
(2, 'Prototipo/Proyecto', 'Información sobre la modalidad de titulación mediante prototipo o proyecto', 1),
(3, 'Experiencia Laboral', 'Información sobre la modalidad de titulación por experiencia laboral', 1),
(4, 'Requisitos', 'Requisitos generales y específicos para el proceso de titulación', 1),
(5, 'Documentos', 'Documentación necesaria para realizar el proceso', 1),
(6, 'Costos', 'Información relacionada con los costos del proceso', 1),
(7, 'Procedimientos', 'Pasos y procedimientos del proceso de titulación', 1),
(8, 'Preguntas frecuentes', 'Preguntas frecuentes realizadas por los alumnos', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `conocimientos`
--

CREATE TABLE `conocimientos` (
  `id` int(11) NOT NULL,
  `categoria_id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `contenido` text NOT NULL,
  `palabras_clave` text DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `conocimientos`
--

INSERT INTO `conocimientos` (`id`, `categoria_id`, `titulo`, `contenido`, `palabras_clave`, `activo`) VALUES
(1, 5, 'Documentación para titulación por Promedio', 'Para realizar el proceso de titulación se requiere presentar: 2 hojas de recepción (datos personales y datos escolares); original y 2 copias del Acta de Nacimiento; original y 2 copias de la CURP; original y 2 copias del Certificado de Secundaria; original y 2 copias del Certificado de Inicio de Bachillerato; original y 2 copias del Certificado de Terminación de Bachillerato; original y 1 copia del Servicio Social; original y 1 copia de las Prácticas; original y 2 copias de cada módulo; original y 2 copias del pago por concepto de titulación; 2 fotografías pequeñas y 6 fotografías grandes.', 'promedio,documentos,documentación,requisitos,papeles,acta de nacimiento,CURP,certificado,secundaria,bachillerato,servicio social,prácticas,módulos,pago,fotografías', 1),
(2, 2, 'Documentación para titulación por Prototipo', 'Para realizar el proceso de titulación por la modalidad de Prototipo se requiere presentar: 2 hojas de recepción (datos personales y datos escolares); original y 2 copias del Acta de Nacimiento; original y 2 copias de la CURP; original y 2 copias del Certificado de Secundaria; original y 2 copias del Certificado de Inicio de Bachillerato; original y 2 copias del Certificado de Terminación de Bachillerato; original y 1 copia del Servicio Social; original y 1 copia de las Prácticas; original y 1 copia de la hoja Dictamen; original y 2 copias del pago por concepto de titulación; 2 fotografías pequeñas y 6 fotografías grandes.', 'prototipo,proyecto,requisitos,documentos,documentación,acta de nacimiento,CURP,certificado,secundaria,bachillerato,servicio social,prácticas,dictamen,pago,fotografías', 1),
(3, 3, 'Documentación para titulación por Experiencia Laboral', 'Para realizar el proceso de titulación por la modalidad de Experiencia Laboral se requiere presentar: 2 hojas de recepción (datos personales y datos escolares); original y 2 copias del Acta de Nacimiento; original y 2 copias de la CURP; original y 2 copias del Certificado de Secundaria; original y 2 copias del Certificado de Inicio de Bachillerato; original y 2 copias del Certificado de Terminación de Bachillerato; original y 1 copia del Servicio Social; original y 1 copia de las Prácticas; original y 1 copia de la Acreditación Laboral; original y 2 copias del pago por concepto de titulación; 2 fotografías pequeñas y 6 fotografías grandes.', 'experiencia laboral,requisitos,documentos,documentación,acreditación laboral,acta de nacimiento,CURP,certificado,secundaria,bachillerato,servicio social,prácticas,pago,fotografías', 1),
(4, 5, 'Fotografías para la titulación', 'La documentación registrada para las modalidades de titulación indica que se requieren 2 fotografías pequeñas y 6 fotografías grandes. Confirma con el área de titulación que estas cantidades correspondan a tu modalidad.', 'fotografias,fotografia,fotos,pequenas,grandes,cantidad,fotografia para titularse', 1),
(5, 8, 'Modalidades de titulación', 'En el CETis 120 se manejan tres modalidades de titulación registradas en este asistente: Promedio, Prototipo/Proyecto y Experiencia Laboral. Los requisitos y documentos pueden variar según la modalidad. Consulta la información específica de cada una para conocer sus requisitos.', 'modalidades,modalidad,opciones,formas,tipos,titulacion,titularme,promedio,prototipo,proyecto,experiencia laboral', 1),
(6, 4, 'Requisitos generales para la titulación', 'Los requisitos y documentos dependen de la modalidad de titulación. En la base de conocimientos del asistente se contemplan las modalidades de Promedio, Prototipo/Proyecto y Experiencia Laboral. Para conocer los documentos que debes entregar, consulta los requisitos específicos de la modalidad que te corresponde. Si tienes dudas sobre tu caso, confirma la información con el área de titulación del CETis 120.', 'requisitos,generales,iniciar,comenzar,tramite,proceso,documentos,modalidades,titulacion', 1),
(7, 8, 'Titulación por promedio', 'La titulación por promedio es una modalidad en la que el alumno puede obtener su título tomando como base su desempeño académico durante sus estudios. Para acceder a esta modalidad, debe cumplir con el promedio mínimo de 8 en todos sus modulos de la carrera que cursa y las demás condiciones establecidas por el plantel. Se recomienda consultar los requisitos oficiales del CETis 120 para conocer las condiciones específicas.', 'promedio,titulacion por promedio,modalidad por promedio,consiste,que es,como funciona,desempeño academico,requisitos', 1),
(8, 8, 'Titulación por prototipo o proyecto', 'La titulación por prototipo o proyecto es una modalidad que consiste en desarrollar y presentar un proyecto relacionado con la formación técnica del alumno. Mediante este trabajo, el estudiante puede demostrar los conocimientos y habilidades que adquirió durante sus estudios. El proyecto debe cumplir con los criterios establecidos por el plantel al ser evaluado por un dictamen, por lo que se recomienda consultar los requisitos oficiales del CETis 120.', 'prototipo,proyecto,titulacion por prototipo,titulacion por proyecto,modalidad de proyecto,consiste,que es,como funciona', 1),
(9, 8, 'Titulación por experiencia laboral', 'La titulación por experiencia laboral es una modalidad relacionada con la experiencia de trabajo del alumno y su relación con los conocimientos adquiridos durante su formación técnica. Para solicitar esta modalidad, el estudiante debe cumplir con las condiciones y presentar la documentación que establezca el plantel. Se recomienda consultar los requisitos oficiales del CETis 120 para conocer las condiciones específicas.', 'experiencia laboral,titulacion por experiencia laboral,trabajo,empleo,modalidad laboral,consiste,que es,como funciona,requisitos', 1),
(10, 8, 'Promedio mínimo para titularse', 'El promedio mínimo para la titulación debe verificarse conforme a los lineamientos vigentes del CETis 120. En otros planteles DGETI se establecen requisitos relacionados con la acreditación de todas las asignaturas y las calificaciones de los módulos profesionales. Antes de elegir esta modalidad, consulta en la oficina de titulación el promedio y las calificaciones mínimas que se exigen en tu generación.', 'promedio minimo,minimo de promedio,cuanto promedio necesito,calificacion minima,calificacion de 7,un siete,modalidad promedio', 1),
(11, 8, 'Materias aprobadas para titularse por promedio', 'Para iniciar el proceso de titulación por promedio, es importante verificar que hayas acreditado todas las asignaturas y que cumplas las condiciones académicas de tu modalidad. Consulta en la oficina de titulación del CETis 120 si existen requisitos adicionales sobre las calificaciones de los módulos profesionales.', 'materias aprobadas,materias reprobadas,adeudar materias,asignaturas,calificaciones,modulos profesionales,promedio', 1),
(12, 8, 'Tiempo de experiencia laboral necesario', 'La titulación por experiencia laboral requiere comprobar que tienes experiencia de trabajo relacionada con tu formación técnica. Como referencia, otros planteles DGETI establecen un mínimo de un año de experiencia laboral. Confirma en la oficina de titulación del CETis 120 el tiempo mínimo vigente y las condiciones que debes cumplir.', 'tiempo trabajando,cuanto tiempo trabajar,cuanto tiempo necesito,un año,meses de trabajo,antiguedad laboral,experiencia laboral', 1),
(13, 8, 'Comprobación de experiencia laboral', 'Para acreditar la experiencia laboral, pueden solicitarse documentos que demuestren el tiempo trabajado y las actividades realizadas, como una constancia laboral emitida por la empresa y un informe de las funciones desempeñadas. Los documentos exactos deben confirmarse con la oficina de titulación del CETis 120.', 'comprobar experiencia,comprobar trabajo,constancia laboral,constancia de empresa,documentos laborales,antiguedad,recibos de nomina', 1),
(14, 8, 'Requisitos del prototipo o proyecto', 'La modalidad de prototipo o proyecto consiste en demostrar conocimientos técnicos mediante el desarrollo de un proyecto relacionado con la especialidad. El plantel puede establecer condiciones sobre el registro, la presentación, la memoria técnica y las evidencias del proyecto. Consulta la convocatoria y los lineamientos vigentes del CETis 120 para conocer los requisitos específicos.', 'requisitos prototipo,requisitos proyecto,proyecto de software,prototipo tecnologico,memoria tecnica,registro de proyecto,equipo,integrantes', 1),
(15, 8, 'Documentos para las modalidades de titulación', 'Los documentos requeridos dependen de la modalidad de titulación elegida. Antes de reunirlos, consulta la lista oficial del CETis 120 para verificar cuáles debes entregar, en qué formato y si se solicitan originales o copias.', 'documentos necesarios,documentos para titularme,papeles,requisitos documentacion,expediente,titulacion,promedio,prototipo,experiencia laboral', 1),
(16, 8, '¿Qué es el proceso de titulación?', 'El proceso de titulación es el conjunto de trámites y requisitos que debe cumplir un alumno para obtener su título como Técnico. En el CETis 120, este proceso permite al estudiante concluir formalmente su formación técnica mediante una de las modalidades de titulación disponibles: Promedio, Prototipo/Proyecto o Experiencia Laboral. Cada modalidad tiene sus propias condiciones y documentación. Por ello, el alumno debe identificar la modalidad que le corresponde, consultar sus requisitos, reunir los documentos solicitados y dar seguimiento a su trámite con el área de titulación del plantel.', 'proceso de titulacion,que es la titulacion,que significa titularse,como es el proceso de titulacion,tramite de titulacion,obtener mi titulo,titulo de tecnico,graduacion,modalidades', 0),
(18, 6, 'Costo Tramite titulacion', 'La titulacion vale 2 pesos', 'costo', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `preguntas_frecuentes`
--

CREATE TABLE `preguntas_frecuentes` (
  `id` int(11) NOT NULL,
  `conocimiento_id` int(11) NOT NULL,
  `pregunta` text NOT NULL,
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `preguntas_frecuentes`
--

INSERT INTO `preguntas_frecuentes` (`id`, `conocimiento_id`, `pregunta`, `activo`) VALUES
(1, 1, '¿Qué documentos necesito para titularme?', 1),
(2, 1, '¿Qué papeles necesito para la titulación?', 1),
(3, 1, '¿Qué debo entregar para iniciar mi titulación?', 1),
(4, 1, '¿Qué documentación tengo que presentar?', 1),
(5, 1, '¿Cuántas copias necesito de mis documentos?', 1),
(6, 1, '¿Necesito llevar el acta de nacimiento?', 1),
(7, 1, '¿Necesito llevar mi CURP?', 1),
(8, 1, '¿Cuántas fotografías necesito para titularme?', 1),
(9, 2, '¿Qué documentos necesito para titularme por prototipo?', 1),
(10, 2, '¿Qué papeles necesito para la modalidad de prototipo?', 1),
(11, 2, '¿Qué debo entregar para titularme por proyecto?', 1),
(12, 2, '¿Qué requisitos tiene la titulación por prototipo?', 1),
(13, 2, '¿Cuántas copias necesito para el prototipo?', 1),
(14, 2, '¿Necesito la hoja dictamen para titularme por prototipo?', 1),
(15, 2, '¿Cuántas fotografías necesito para el prototipo?', 1),
(16, 3, '¿Qué documentos necesito para titularme por experiencia laboral?', 1),
(17, 3, '¿Qué papeles necesito para la modalidad de experiencia laboral?', 1),
(18, 3, '¿Qué debo entregar para titularme por experiencia laboral?', 1),
(19, 3, '¿Qué requisitos tiene la titulación por experiencia laboral?', 1),
(20, 3, '¿Cuántas copias necesito para experiencia laboral?', 1),
(21, 3, '¿Necesito la acreditación laboral?', 1),
(22, 3, '¿Cuántas fotografías necesito para experiencia laboral?', 1),
(25, 4, '¿Cuántas fotografías necesito para titularme?', 1),
(26, 5, '¿Qué modalidades de titulación existen?', 1),
(27, 5, '¿Cuáles son mis opciones para titularme?', 1),
(28, 5, '¿Cómo me puedo titular?', 1),
(29, 5, '¿Qué modalidades ofrece el CETis 120?', 1),
(30, 5, '¿Qué tipos de titulación hay?', 1),
(31, 6, '¿Cuáles son los requisitos generales para titularme?', 1),
(32, 6, '¿Qué necesito para iniciar mi titulación?', 1),
(33, 6, '¿Qué debo tener antes de comenzar el trámite?', 1),
(34, 6, '¿Qué necesito para empezar el proceso de titulación?', 1),
(35, 7, '¿En qué consiste la titulación por promedio?', 1),
(36, 7, '¿Qué es la titulación por promedio?', 1),
(37, 7, 'Explícame la modalidad de promedio.', 1),
(38, 8, '¿En qué consiste la titulación por prototipo?', 1),
(39, 8, '¿Qué es la titulación por proyecto?', 1),
(40, 8, 'Explícame la modalidad de prototipo.', 1),
(41, 9, '¿En qué consiste la titulación por experiencia laboral?', 1),
(42, 9, '¿Qué es la titulación por experiencia laboral?', 1),
(43, 9, 'Explícame la modalidad de experiencia laboral.', 1),
(44, 10, '¿Cuál es el promedio mínimo que necesito para titularme?', 1),
(45, 10, '¿Qué calificación mínima necesito en los módulos?', 1),
(46, 10, '¿Puedo titularme por promedio si tengo un 7 en un módulo?', 1),
(47, 11, '¿Necesito tener todas las materias aprobadas?', 1),
(48, 11, '¿Puedo titularme si debo una materia?', 1),
(49, 12, '¿Cuánto tiempo tengo que trabajar para titularme?', 1),
(50, 12, '¿Necesito un año de experiencia laboral?', 1),
(51, 12, '¿Puedo titularme por experiencia si llevo menos de un año trabajando?', 1),
(52, 13, '¿Cómo compruebo mi experiencia laboral?', 1),
(53, 13, '¿Necesito una constancia de la empresa?', 1),
(54, 13, '¿Qué documentos sirven para demostrar que trabajé?', 1),
(55, 14, '¿Qué requisitos debe cumplir mi prototipo?', 1),
(56, 14, '¿Puedo hacer un proyecto de software?', 1),
(57, 14, '¿Necesito presentar una memoria técnica?', 1),
(58, 14, '¿Puedo hacer el proyecto en equipo?', 1),
(59, 15, '¿Qué documentos necesito para titularme?', 1),
(60, 15, '¿Qué papeles debo entregar para mi modalidad?', 1),
(61, 16, '¿Qué es el proceso de titulación?', 1),
(62, 16, '¿En qué consiste el proceso de titulación?', 1),
(63, 16, '¿Qué debo hacer para obtener mi título?', 1),
(64, 16, '¿Cómo funciona el trámite de titulación?', 1),
(65, 16, '¿Qué significa titularse?', 1),
(68, 18, '¿Cuánto cuesta el costo del tramite de titulación?', 1);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `conocimientos`
--
ALTER TABLE `conocimientos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `categoria_id` (`categoria_id`);

--
-- Indices de la tabla `preguntas_frecuentes`
--
ALTER TABLE `preguntas_frecuentes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `conocimiento_id` (`conocimiento_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `conocimientos`
--
ALTER TABLE `conocimientos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de la tabla `preguntas_frecuentes`
--
ALTER TABLE `preguntas_frecuentes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `conocimientos`
--
ALTER TABLE `conocimientos`
  ADD CONSTRAINT `conocimientos_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `preguntas_frecuentes`
--
ALTER TABLE `preguntas_frecuentes`
  ADD CONSTRAINT `preguntas_frecuentes_ibfk_1` FOREIGN KEY (`conocimiento_id`) REFERENCES `conocimientos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
