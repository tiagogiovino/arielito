-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 03-11-2026 a las 21:50:18
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
-- Base de datos: `sistema_examenes`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `acceso_docente`
--

CREATE TABLE `acceso_docente` (
  `id_acceso` int(11) NOT NULL,
  `id_docente` int(11) NOT NULL,
  `usuario` varchar(120) NOT NULL,
  `password_hash` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `acceso_docente`
--

INSERT INTO `acceso_docente` (`id_acceso`, `id_docente`, `usuario`, `password_hash`) VALUES
(1, 1, 'Sofía Martínez', '$2y$12$/7zMnwontAlRzFwpiuhp8ePU1UeSOra.QkJaKBLSKQ5xt8o9fQW.u'),
(2, 2, 'Diego Ramírez', '$2y$12$TjWqlzuImOaBwYWEZjfygODCxQbIB6Bnv0Oqz6HtRP0f4xpnINRr.'),
(3, 3, 'Valentina López', '$2y$12$ds7W3ccBuLMyiOSnCmlfsu4cFYX92DwURFYQ4BhJojOaXN7k2JDza'),
(4, 4, 'Matías Sosa', '$2y$12$13yPFeyuRBY7sFMAip06SehrbKN6Yh0X8.hyARl15K57E0Z2yzNJq'),
(5, 5, 'Camila Torres', '$2y$12$p1aOz2P5lfTktItWevMjneGg7bNxcurafqGkzRuoW/De2Bz7MdFk6'),
(6, 6, 'Nicolás Acosta', '$2y$12$sK64nqxbmolkTJ2tRyKWWOFoS5Hc57CHYFpFcguOcyoNd3J49uN9C'),
(7, 7, 'Julieta Romero', '$2y$12$/EloNf6xfr2ixAXLNc7HKud35ZVQt6OyTtvZpqqpumc0rCEgnI3Jm'),
(8, 8, 'Tomás Herrera', '$2y$12$x3IT9pYs/AeY77x7uemtke0nEpbaxnXX29yoLdqLqErMZ4HGiVXrC'),
(9, 9, 'Milagros Castro', '$2y$12$R9becqDlZM2v96ajSROx9efR5MIIyRU2HXCRXjGAGqAW0U60jm1eC'),
(10, 10, 'Facundo Medina', '$2y$12$nBvNeXriiF3pY2DQmsb8cOmUUYs0Xw9CEI2WbAqDoShsSIdvjQAky');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `acceso_preceptor`
--

CREATE TABLE `acceso_preceptor` (
  `id_acceso` int(11) NOT NULL,
  `id_preceptor` int(11) NOT NULL,
  `usuario` varchar(120) NOT NULL,
  `password_hash` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `alumno`
--

CREATE TABLE `alumno` (
  `id_alumno` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `apellido` varchar(50) NOT NULL,
  `dni` varchar(20) NOT NULL,
  `curso` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `alumno`
--

INSERT INTO `alumno` (`id_alumno`, `nombre`, `apellido`, `dni`, `curso`) VALUES
(1, 'daniel', 'yalan', '57421430', '5to 2da'),
(2, 'tiago', 'barrios', '44564325', '7mo 1ra');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `aula`
--

CREATE TABLE `aula` (
  `id_aula` int(11) NOT NULL,
  `numero` varchar(10) NOT NULL,
  `capacidad` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `aula`
--

INSERT INTO `aula` (`id_aula`, `numero`, `capacidad`) VALUES
(1, '4to 1ra', 26);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comprobante`
--

CREATE TABLE `comprobante` (
  `id_comprobante` int(11) NOT NULL,
  `fecha_emision` datetime NOT NULL,
  `pdf` varchar(255) DEFAULT NULL,
  `qr` varchar(255) DEFAULT NULL,
  `id_inscripcion` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `correlativa`
--

CREATE TABLE `correlativa` (
  `id_materia` int(11) NOT NULL,
  `id_materia_correlativa` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `correlativa`
--

INSERT INTO `correlativa` (`id_materia`, `id_materia_correlativa`) VALUES
(2, 1),
(3, 2),
(5, 4),
(7, 6),
(9, 8),
(11, 10),
(16, 15),
(17, 16),
(18, 15);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `docente`
--

CREATE TABLE `docente` (
  `id_docente` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `apellido` varchar(50) NOT NULL,
  `dni` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `docente`
--

INSERT INTO `docente` (`id_docente`, `nombre`, `apellido`, `dni`) VALUES
(1, 'Sofía', 'Martínez', '28000101'),
(2, 'Diego', 'Ramírez', '28000102'),
(3, 'Valentina', 'López', '28000103'),
(4, 'Matías', 'Sosa', '28000104'),
(5, 'Camila', 'Torres', '28000105'),
(6, 'Nicolás', 'Acosta', '28000106'),
(7, 'Julieta', 'Romero', '28000107'),
(8, 'Tomás', 'Herrera', '28000108'),
(9, 'Milagros', 'Castro', '28000109'),
(10, 'Facundo', 'Medina', '28000110');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inscripcion`
--

CREATE TABLE `inscripcion` (
  `id_inscripcion` int(11) NOT NULL,
  `fecha_inscripcion` date NOT NULL,
  `estado` varchar(30) DEFAULT NULL,
  `id_alumno` int(11) NOT NULL,
  `id_mesa` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `inscripcion`
--

INSERT INTO `inscripcion` (`id_inscripcion`, `fecha_inscripcion`, `estado`, `id_alumno`, `id_mesa`) VALUES
(1, '2026-10-28', 'Inscripto', 2, 1),
(2, '2026-10-28', 'Inscripto', 2, 1),
(3, '2026-10-28', 'Inscripto', 1, 6);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `materia`
--

CREATE TABLE `materia` (
  `id_materia` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `materia`
--

INSERT INTO `materia` (`id_materia`, `nombre`) VALUES
(18, 'Base de Datos'),
(12, 'Biología'),
(13, 'Física'),
(10, 'Geografía I'),
(11, 'Geografía II'),
(8, 'Historia I'),
(9, 'Historia II'),
(6, 'Inglés I'),
(7, 'Inglés II'),
(15, 'Introducción a la Informática'),
(4, 'Lengua y Literatura I'),
(5, 'Lengua y Literatura II'),
(1, 'Matemática I'),
(2, 'Matemática II'),
(3, 'Matemática III'),
(16, 'Programación I'),
(17, 'Programación II'),
(14, 'Química');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mesa`
--

CREATE TABLE `mesa` (
  `id_mesa` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `horario` time NOT NULL,
  `id_aula` int(11) DEFAULT NULL,
  `id_docente` int(11) DEFAULT NULL,
  `id_materia` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `mesa`
--

INSERT INTO `mesa` (`id_mesa`, `fecha`, `horario`, `id_aula`, `id_docente`, `id_materia`) VALUES
(1, '2026-11-12', '02:50:00', 1, 5, 17),
(6, '2026-11-13', '03:20:00', 1, 6, 18);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `nota`
--

CREATE TABLE `nota` (
  `id_nota` int(11) NOT NULL,
  `calificacion` decimal(4,2) DEFAULT NULL,
  `fecha` date DEFAULT NULL,
  `id_inscripcion` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `nota`
--

INSERT INTO `nota` (`id_nota`, `calificacion`, `fecha`, `id_inscripcion`) VALUES
(1, 7.00, '2026-10-28', 3);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `preceptor`
--

CREATE TABLE `preceptor` (
  `id_preceptor` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `apellido` varchar(50) NOT NULL,
  `dni` varchar(20) NOT NULL,
  `usuario` varchar(120) NOT NULL,
  `contraseña` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `preceptor`
--

INSERT INTO `preceptor` (`id_preceptor`, `nombre`, `apellido`, `dni`, `usuario`, `contraseña`) VALUES
(1, 'Lucía', 'Fernández', '39000101', 'Lucía Fernández', '39000101'),
(2, 'Marcos', 'Benítez', '39000102', 'Marcos Benítez', '39000102'),
(3, 'Carolina', 'Gómez', '39000103', 'Carolina Gómez', '39000103');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `registro_ingresos_alumnos`
--

CREATE TABLE `registro_ingresos_alumnos` (
  `id_registro` bigint(20) UNSIGNED NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `usuario` varchar(150) NOT NULL,
  `fecha_hora` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `registro_ingresos_alumnos`
--

INSERT INTO `registro_ingresos_alumnos` (`id_registro`, `id_usuario`, `usuario`, `fecha_hora`) VALUES
(1, 1, 'daniel yalan', '2026-10-28 16:55:11'),
(2, 2, 'tiago barrios', '2026-10-28 16:55:50'),
(3, 2, 'tiago barrios', '2026-10-28 17:00:27'),
(4, 1, 'daniel yalan', '2026-10-28 17:00:45'),
(5, 2, 'tiago barrios', '2026-10-28 17:02:50'),
(6, 1, 'daniel yalan', '2026-10-28 17:03:01');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `usuario` varchar(150) NOT NULL,
  `contraseña` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `nombre`, `usuario`, `contraseña`) VALUES
(1, 'daniel yalan', 'daniel yalan', '$2y$10$FhXQJglX2sWNc/AU957hjubYK2hs/8CcmUPnXQH0IcmHCXoRVLM1u'),
(2, 'tiago barrios', 'tiago barrios', '$2y$10$pSMDdvxxCpxyIS8jEw15w.FNWyyQ8cQ8vixtp6SdBDGEzk8L95GI.');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `validacion_ingreso`
--

CREATE TABLE `validacion_ingreso` (
  `id_validacion` int(11) NOT NULL,
  `fecha_hora` datetime NOT NULL,
  `resultado` varchar(30) DEFAULT NULL,
  `id_comprobante` int(11) NOT NULL,
  `id_preceptor` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `acceso_docente`
--
ALTER TABLE `acceso_docente`
  ADD PRIMARY KEY (`id_acceso`),
  ADD UNIQUE KEY `id_docente` (`id_docente`),
  ADD UNIQUE KEY `usuario` (`usuario`);

--
-- Indices de la tabla `acceso_preceptor`
--
ALTER TABLE `acceso_preceptor`
  ADD PRIMARY KEY (`id_acceso`),
  ADD UNIQUE KEY `id_preceptor` (`id_preceptor`),
  ADD UNIQUE KEY `usuario` (`usuario`);

--
-- Indices de la tabla `alumno`
--
ALTER TABLE `alumno`
  ADD PRIMARY KEY (`id_alumno`),
  ADD UNIQUE KEY `dni` (`dni`);

--
-- Indices de la tabla `aula`
--
ALTER TABLE `aula`
  ADD PRIMARY KEY (`id_aula`);

--
-- Indices de la tabla `comprobante`
--
ALTER TABLE `comprobante`
  ADD PRIMARY KEY (`id_comprobante`),
  ADD UNIQUE KEY `id_inscripcion` (`id_inscripcion`);

--
-- Indices de la tabla `correlativa`
--
ALTER TABLE `correlativa`
  ADD PRIMARY KEY (`id_materia`,`id_materia_correlativa`),
  ADD KEY `fk_correlativa_requisito` (`id_materia_correlativa`);

--
-- Indices de la tabla `docente`
--
ALTER TABLE `docente`
  ADD PRIMARY KEY (`id_docente`),
  ADD UNIQUE KEY `dni` (`dni`);

--
-- Indices de la tabla `inscripcion`
--
ALTER TABLE `inscripcion`
  ADD PRIMARY KEY (`id_inscripcion`),
  ADD KEY `id_alumno` (`id_alumno`),
  ADD KEY `id_mesa` (`id_mesa`);

--
-- Indices de la tabla `materia`
--
ALTER TABLE `materia`
  ADD PRIMARY KEY (`id_materia`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `mesa`
--
ALTER TABLE `mesa`
  ADD PRIMARY KEY (`id_mesa`),
  ADD KEY `id_aula` (`id_aula`),
  ADD KEY `id_docente` (`id_docente`),
  ADD KEY `id_materia` (`id_materia`);

--
-- Indices de la tabla `nota`
--
ALTER TABLE `nota`
  ADD PRIMARY KEY (`id_nota`),
  ADD UNIQUE KEY `id_inscripcion` (`id_inscripcion`);

--
-- Indices de la tabla `preceptor`
--
ALTER TABLE `preceptor`
  ADD PRIMARY KEY (`id_preceptor`),
  ADD UNIQUE KEY `dni` (`dni`),
  ADD UNIQUE KEY `usuario` (`usuario`);

--
-- Indices de la tabla `registro_ingresos_alumnos`
--
ALTER TABLE `registro_ingresos_alumnos`
  ADD PRIMARY KEY (`id_registro`),
  ADD KEY `idx_registro_alumno` (`id_usuario`),
  ADD KEY `idx_registro_fecha` (`fecha_hora`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `usuario` (`usuario`);

--
-- Indices de la tabla `validacion_ingreso`
--
ALTER TABLE `validacion_ingreso`
  ADD PRIMARY KEY (`id_validacion`),
  ADD KEY `id_comprobante` (`id_comprobante`),
  ADD KEY `id_preceptor` (`id_preceptor`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `acceso_docente`
--
ALTER TABLE `acceso_docente`
  MODIFY `id_acceso` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `acceso_preceptor`
--
ALTER TABLE `acceso_preceptor`
  MODIFY `id_acceso` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `alumno`
--
ALTER TABLE `alumno`
  MODIFY `id_alumno` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `aula`
--
ALTER TABLE `aula`
  MODIFY `id_aula` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `comprobante`
--
ALTER TABLE `comprobante`
  MODIFY `id_comprobante` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `docente`
--
ALTER TABLE `docente`
  MODIFY `id_docente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `inscripcion`
--
ALTER TABLE `inscripcion`
  MODIFY `id_inscripcion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `materia`
--
ALTER TABLE `materia`
  MODIFY `id_materia` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de la tabla `mesa`
--
ALTER TABLE `mesa`
  MODIFY `id_mesa` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `nota`
--
ALTER TABLE `nota`
  MODIFY `id_nota` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `preceptor`
--
ALTER TABLE `preceptor`
  MODIFY `id_preceptor` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `registro_ingresos_alumnos`
--
ALTER TABLE `registro_ingresos_alumnos`
  MODIFY `id_registro` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `validacion_ingreso`
--
ALTER TABLE `validacion_ingreso`
  MODIFY `id_validacion` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `acceso_docente`
--
ALTER TABLE `acceso_docente`
  ADD CONSTRAINT `acceso_docente_ibfk_1` FOREIGN KEY (`id_docente`) REFERENCES `docente` (`id_docente`) ON DELETE CASCADE;

--
-- Filtros para la tabla `acceso_preceptor`
--
ALTER TABLE `acceso_preceptor`
  ADD CONSTRAINT `acceso_preceptor_ibfk_1` FOREIGN KEY (`id_preceptor`) REFERENCES `preceptor` (`id_preceptor`) ON DELETE CASCADE;

--
-- Filtros para la tabla `comprobante`
--
ALTER TABLE `comprobante`
  ADD CONSTRAINT `comprobante_ibfk_1` FOREIGN KEY (`id_inscripcion`) REFERENCES `inscripcion` (`id_inscripcion`);

--
-- Filtros para la tabla `correlativa`
--
ALTER TABLE `correlativa`
  ADD CONSTRAINT `fk_correlativa_materia` FOREIGN KEY (`id_materia`) REFERENCES `materia` (`id_materia`),
  ADD CONSTRAINT `fk_correlativa_requisito` FOREIGN KEY (`id_materia_correlativa`) REFERENCES `materia` (`id_materia`);

--
-- Filtros para la tabla `inscripcion`
--
ALTER TABLE `inscripcion`
  ADD CONSTRAINT `inscripcion_ibfk_1` FOREIGN KEY (`id_alumno`) REFERENCES `alumno` (`id_alumno`),
  ADD CONSTRAINT `inscripcion_ibfk_2` FOREIGN KEY (`id_mesa`) REFERENCES `mesa` (`id_mesa`);

--
-- Filtros para la tabla `mesa`
--
ALTER TABLE `mesa`
  ADD CONSTRAINT `mesa_ibfk_1` FOREIGN KEY (`id_aula`) REFERENCES `aula` (`id_aula`),
  ADD CONSTRAINT `mesa_ibfk_2` FOREIGN KEY (`id_docente`) REFERENCES `docente` (`id_docente`),
  ADD CONSTRAINT `mesa_ibfk_3` FOREIGN KEY (`id_materia`) REFERENCES `materia` (`id_materia`);

--
-- Filtros para la tabla `nota`
--
ALTER TABLE `nota`
  ADD CONSTRAINT `nota_ibfk_1` FOREIGN KEY (`id_inscripcion`) REFERENCES `inscripcion` (`id_inscripcion`);

--
-- Filtros para la tabla `validacion_ingreso`
--
ALTER TABLE `validacion_ingreso`
  ADD CONSTRAINT `validacion_ingreso_ibfk_1` FOREIGN KEY (`id_comprobante`) REFERENCES `comprobante` (`id_comprobante`),
  ADD CONSTRAINT `validacion_ingreso_ibfk_2` FOREIGN KEY (`id_preceptor`) REFERENCES `preceptor` (`id_preceptor`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
