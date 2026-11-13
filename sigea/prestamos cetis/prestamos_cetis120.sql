-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 15-10-2026 a las 05:21:07
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
-- Base de datos: `prestamos_cetis120`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_solicitud`
--

CREATE TABLE `detalle_solicitud` (
  `id_detalle` int(10) UNSIGNED NOT NULL,
  `folio` int(10) UNSIGNED NOT NULL,
  `id_material` int(10) UNSIGNED NOT NULL,
  `cantidad` int(10) UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `materiales`
--

CREATE TABLE `materiales` (
  `id_material` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `categoria` varchar(80) NOT NULL,
  `cantidad_total` int(10) UNSIGNED NOT NULL DEFAULT 3,
  `cantidad_disponible` int(10) UNSIGNED NOT NULL DEFAULT 3,
  `estado` enum('Disponible','Agotado') NOT NULL DEFAULT 'Disponible'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `materiales`
--

INSERT INTO `materiales` (`id_material`, `nombre`, `categoria`, `cantidad_total`, `cantidad_disponible`, `estado`) VALUES
(1, 'Computadoras', 'Electrónica', 3, 3, 'Disponible'),
(2, 'Cargadores', 'Electrónica', 3, 3, 'Disponible'),
(3, 'Adaptadores', 'Electrónica', 3, 3, 'Disponible'),
(4, 'Calculadoras', 'Escolar', 3, 3, 'Disponible'),
(5, 'Cables HDMI', 'Electrónica', 3, 3, 'Disponible'),
(6, 'Cables de red', 'Electrónica', 3, 3, 'Disponible'),
(7, 'Extensiones', 'Electrónica', 3, 3, 'Disponible'),
(8, 'Proyectores', 'Electrónica', 3, 3, 'Disponible'),
(9, 'Routers', 'Electrónica', 3, 3, 'Disponible'),
(10, 'Cables de alimentación', 'Electrónica', 3, 3, 'Disponible');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `solicitudes`
--

CREATE TABLE `solicitudes` (
  `folio` int(10) UNSIGNED NOT NULL,
  `nombre_alumno` varchar(150) NOT NULL,
  `numero_control` varchar(30) NOT NULL,
  `especialidad` varchar(100) NOT NULL,
  `turno` varchar(20) NOT NULL,
  `tiempo_uso` varchar(30) NOT NULL,
  `materiales` varchar(255) NOT NULL,
  `fecha_solicitud` timestamp NOT NULL DEFAULT current_timestamp(),
  `estado` varchar(30) NOT NULL DEFAULT 'Pendiente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `solicitudes`
--

INSERT INTO `solicitudes` (`folio`, `nombre_alumno`, `numero_control`, `especialidad`, `turno`, `tiempo_uso`, `materiales`, `fecha_solicitud`, `estado`) VALUES
(1, 'cristian ricardo bautista gaona', '24316061200103', 'Programación', 'Matutino', '2 horas', 'Cargadores, Extensiones', '2026-10-14 20:12:21', 'Pendiente');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `detalle_solicitud`
--
ALTER TABLE `detalle_solicitud`
  ADD PRIMARY KEY (`id_detalle`),
  ADD UNIQUE KEY `uq_solicitud_material` (`folio`,`id_material`),
  ADD KEY `fk_detalle_material` (`id_material`);

--
-- Indices de la tabla `materiales`
--
ALTER TABLE `materiales`
  ADD PRIMARY KEY (`id_material`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `solicitudes`
--
ALTER TABLE `solicitudes`
  ADD PRIMARY KEY (`folio`),
  ADD KEY `idx_solicitudes_fecha` (`fecha_solicitud`),
  ADD KEY `idx_solicitudes_control` (`numero_control`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `detalle_solicitud`
--
ALTER TABLE `detalle_solicitud`
  MODIFY `id_detalle` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `materiales`
--
ALTER TABLE `materiales`
  MODIFY `id_material` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `solicitudes`
--
ALTER TABLE `solicitudes`
  MODIFY `folio` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `detalle_solicitud`
--
ALTER TABLE `detalle_solicitud`
  ADD CONSTRAINT `fk_detalle_material` FOREIGN KEY (`id_material`) REFERENCES `materiales` (`id_material`),
  ADD CONSTRAINT `fk_detalle_solicitud` FOREIGN KEY (`folio`) REFERENCES `solicitudes` (`folio`) ON DELETE CASCADE;
-- Cuentas de alumnos para crear cuenta e iniciar sesión desde la página principal.
-- Las contraseñas se guardan como hash generado por PHP, nunca en texto plano.
CREATE TABLE IF NOT EXISTS `cuentas_alumnos` (
  `numero_control` varchar(14) NOT NULL,
  `nombre_alumno` varchar(150) NOT NULL,
  `especialidad` varchar(100) NOT NULL,
  `turno` varchar(20) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`numero_control`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
