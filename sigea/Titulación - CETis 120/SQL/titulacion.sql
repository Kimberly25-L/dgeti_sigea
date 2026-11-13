-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 22-10-2026 a las 21:23:47
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
-- Base de datos: `titulacion`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `alumnos_experiencia`
--

CREATE TABLE `alumnos_experiencia` (
  `id` int(11) NOT NULL,
  `no_control` varchar(20) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellidos` varchar(150) NOT NULL,
  `empresa` varchar(200) NOT NULL,
  `estado` varchar(50) NOT NULL DEFAULT 'En proceso',
  `doc_identidad` tinyint(1) NOT NULL DEFAULT 0,
  `doc_escolares` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `alumnos_promedio`
--

CREATE TABLE `alumnos_promedio` (
  `id` int(11) NOT NULL,
  `no_control` varchar(20) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellidos` varchar(150) NOT NULL,
  `promedio` decimal(3,1) NOT NULL,
  `estado` varchar(50) NOT NULL DEFAULT 'En proceso',
  `doc_identidad` tinyint(1) NOT NULL DEFAULT 0,
  `doc_escolares` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `alumnos_prototipo`
--

CREATE TABLE `alumnos_prototipo` (
  `id` int(11) NOT NULL,
  `no_control` varchar(20) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellidos` varchar(150) NOT NULL,
  `proyecto` varchar(200) NOT NULL,
  `estado` varchar(50) NOT NULL DEFAULT 'En proceso',
  `doc_identidad` tinyint(1) NOT NULL DEFAULT 0,
  `doc_escolares` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `usuario`, `password`) VALUES
(1, 'admin', '$2y$10$yYeR7I86.tj/RvM6DL1KbuEXFWNOJsVQOVDkpVVWld861bHkA4Zk6');

--
-- Estructura de tabla para la tabla `intentos_login`
-- (bloqueo: 8 intentos fallidos = 15 minutos; login.php también la crea sola)
--

CREATE TABLE IF NOT EXISTS `intentos_login` (
  `usuario` varchar(50) NOT NULL,
  `intentos` int(11) NOT NULL DEFAULT 0,
  `bloqueado_hasta` datetime DEFAULT NULL,
  `ultimo_intento` datetime DEFAULT NULL,
  PRIMARY KEY (`usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `alumnos_experiencia`
--
ALTER TABLE `alumnos_experiencia`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `no_control` (`no_control`);

--
-- Indices de la tabla `alumnos_promedio`
--
ALTER TABLE `alumnos_promedio`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `no_control` (`no_control`);

--
-- Indices de la tabla `alumnos_prototipo`
--
ALTER TABLE `alumnos_prototipo`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `no_control` (`no_control`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario` (`usuario`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `alumnos_experiencia`
--
ALTER TABLE `alumnos_experiencia`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `alumnos_promedio`
--
ALTER TABLE `alumnos_promedio`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT de la tabla `alumnos_prototipo`
--
ALTER TABLE `alumnos_prototipo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
