-- phpMyAdmin SQL Dump
-- version 5.0.2
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 14-08-2020 a las 13:54:48
-- Versión del servidor: 10.4.13-MariaDB
-- Versión de PHP: 7.4.7

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `todoapp`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `equipos`
--

CREATE TABLE `equipos` (
  `id` int(99) NOT NULL,
  `nombre` varchar(50) COLLATE utf8_spanish2_ci NOT NULL,
  `creador` varchar(255) COLLATE utf8_spanish2_ci NOT NULL,
  `imagen` varchar(100) COLLATE utf8_spanish2_ci NOT NULL,
  `descripcion` varchar(255) COLLATE utf8_spanish2_ci NOT NULL,
  `creacion` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

--
-- Volcado de datos para la tabla `equipos`
--

INSERT INTO `equipos` (`id`, `nombre`, `creador`, `imagen`, `descripcion`, `creacion`) VALUES
(68, 'XimoTeam', '1', 'default2.jpg', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.', '2020-08-14');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tareas`
--

CREATE TABLE `tareas` (
  `id` int(99) NOT NULL,
  `idUsuario` int(11) NOT NULL,
  `titulo` varchar(50) COLLATE utf8_spanish2_ci NOT NULL,
  `fecha` date NOT NULL,
  `descripcion` varchar(255) COLLATE utf8_spanish2_ci NOT NULL,
  `creacion` datetime(6) NOT NULL,
  `completada` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

--
-- Volcado de datos para la tabla `tareas`
--

INSERT INTO `tareas` (`id`, `idUsuario`, `titulo`, `fecha`, `descripcion`, `creacion`, `completada`) VALUES
(27, 1, 'test', '2020-08-14', 'sdsd', '2020-08-14 10:27:05.000000', 0),
(29, 1, 'Segunda tarea del día', '2020-08-14', 'test de una tarea', '2020-08-14 10:46:45.000000', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(99) NOT NULL,
  `token` varchar(255) COLLATE utf8_spanish2_ci NOT NULL,
  `nombre` varchar(60) COLLATE utf8_spanish2_ci NOT NULL,
  `usuario` varchar(60) COLLATE utf8_spanish2_ci NOT NULL,
  `email` varchar(60) COLLATE utf8_spanish2_ci NOT NULL,
  `clave` varchar(255) COLLATE utf8_spanish2_ci NOT NULL,
  `imagen` varchar(255) COLLATE utf8_spanish2_ci NOT NULL,
  `verificado` int(11) NOT NULL,
  `tema` int(11) NOT NULL,
  `ubicacion` varchar(90) COLLATE utf8_spanish2_ci NOT NULL,
  `rol` varchar(50) COLLATE utf8_spanish2_ci NOT NULL,
  `descripcion` varchar(255) COLLATE utf8_spanish2_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `token`, `nombre`, `usuario`, `email`, `clave`, `imagen`, `verificado`, `tema`, `ubicacion`, `rol`, `descripcion`) VALUES
(1, '7a9012bc6b0961287bc9627104b4b6d6', 'Ximo Romero Esteve', 'Ximoromero5', 'ximore55@gmail.com', 'ScMbgBL3zfqnI', 'fc77bc2792bde055.png', 1, 0, 'Valencia, Valencia, Spain', 'Full Stack Web Developer', 'Descripción de pruebaaa'),
(16, '053fdec039509e2b3bc70f6e10d2365d', 'Cuenta de test', 'test', 'test@test.com', 'ScMbgBL3zfqnI', 'd6b850c0fab49180.jpg', 1, 0, 'Valladolid, Castille and León, Spain', 'test', 'test'),
(17, '580f481565082f33df8013c7a3ad378d', 'viva', 'viva', 'joaquin@vivaconversion.com', 'ScMbgBL3zfqnI', '992cb7df3e37caac.jpg', 1, 0, 'Zaragoza, Aragon, Spain', 'Test account', 'wewewe'),
(28, '3300fc00573bcac4c54f132ed7f33560', 'Paula', 'paula34', 'paula@gmail.com', 'ScMbgBL3zfqnI', '05aa88ebd82598a2.jpg', 1, 0, 'Valencia, Valencia, Spain', '', ''),
(36, '6a6195c58584e4f0790219e9c9a632f4', 'Pedro García', 'pedro12', 'pedro@gmail.com', 'ScMbgBL3zfqnI', '3439be4f5a146d6d.jpg', 1, 0, 'Barcelona, Catalonia, Spain', 'Tester', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Duis dignissim, quam eget scelerisque blandit, tortor quam egestas felis, quis scelerisque l');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios_equipos`
--

CREATE TABLE `usuarios_equipos` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_equipo` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

--
-- Volcado de datos para la tabla `usuarios_equipos`
--

INSERT INTO `usuarios_equipos` (`id`, `id_usuario`, `id_equipo`) VALUES
(84, 17, 68),
(86, 36, 68),
(87, 28, 68),
(88, 1, 68);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios_tareas`
--

CREATE TABLE `usuarios_tareas` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_tarea` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

--
-- Volcado de datos para la tabla `usuarios_tareas`
--

INSERT INTO `usuarios_tareas` (`id`, `id_usuario`, `id_tarea`) VALUES
(1, 1, 27),
(3, 1, 29),
(4, 17, 27),
(5, 36, 27),
(6, 17, 29);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `equipos`
--
ALTER TABLE `equipos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `tareas`
--
ALTER TABLE `tareas`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `usuarios_equipos`
--
ALTER TABLE `usuarios_equipos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_usuario` (`id_usuario`),
  ADD KEY `id_equipo` (`id_equipo`) USING BTREE;

--
-- Indices de la tabla `usuarios_tareas`
--
ALTER TABLE `usuarios_tareas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_tarea` (`id_tarea`),
  ADD KEY `id_usuario` (`id_usuario`) USING BTREE;

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `equipos`
--
ALTER TABLE `equipos`
  MODIFY `id` int(99) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

--
-- AUTO_INCREMENT de la tabla `tareas`
--
ALTER TABLE `tareas`
  MODIFY `id` int(99) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(99) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT de la tabla `usuarios_equipos`
--
ALTER TABLE `usuarios_equipos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=89;

--
-- AUTO_INCREMENT de la tabla `usuarios_tareas`
--
ALTER TABLE `usuarios_tareas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `usuarios_equipos`
--
ALTER TABLE `usuarios_equipos`
  ADD CONSTRAINT `usuarios_equipos_ibfk_2` FOREIGN KEY (`id_equipo`) REFERENCES `equipos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `usuarios_equipos_ibfk_3` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `usuarios_tareas`
--
ALTER TABLE `usuarios_tareas`
  ADD CONSTRAINT `usuarios_tareas_ibfk_2` FOREIGN KEY (`id_tarea`) REFERENCES `tareas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `usuarios_tareas_ibfk_3` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
