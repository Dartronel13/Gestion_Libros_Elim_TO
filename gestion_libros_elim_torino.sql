-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:8889
-- Creato il: Gen 27, 2026 alle 10:59
-- Versione del server: 8.0.40
-- Versione PHP: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `gestion_libros_elim_torino`
--

-- --------------------------------------------------------

--
-- Struttura della tabella `categorias`
--

CREATE TABLE `categorias` (
  `id` int NOT NULL,
  `nombre` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Dump dei dati per la tabella `categorias`
--

INSERT INTO `categorias` (`id`, `nombre`) VALUES
(8, 'Biblias'),
(3, 'Comentarios'),
(6, 'Concordancias'),
(7, 'Cultura Hebrea'),
(5, 'Devocionales'),
(2, 'Diccionarios'),
(11, 'Guias de Celula'),
(1, 'Historicos'),
(9, 'Obras Bíblicas'),
(10, 'Obras Múltiples'),
(4, 'Teológicos');

-- --------------------------------------------------------

--
-- Struttura della tabella `lectores`
--

CREATE TABLE `lectores` (
  `id` int NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `direccion` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `codigo_fiscal` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Struttura della tabella `libros`
--

CREATE TABLE `libros` (
  `id` int NOT NULL,
  `codigo_interno` varchar(30) NOT NULL,
  `titulo` varchar(200) NOT NULL,
  `autor` varchar(150) NOT NULL,
  `año_publicacion` int DEFAULT NULL,
  `isbn` varchar(20) DEFAULT NULL,
  `stock` int DEFAULT '1',
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `activo` tinyint DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Struttura della tabella `libro_categoria`
--

CREATE TABLE `libro_categoria` (
  `id_libro` int NOT NULL,
  `id_categoria` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Struttura della tabella `logs_actividad`
--

CREATE TABLE `logs_actividad` (
  `id` int NOT NULL,
  `usuario_id` int DEFAULT NULL,
  `accion` varchar(50) NOT NULL,
  `modulo` varchar(30) NOT NULL,
  `descripcion` text,
  `fecha` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Dump dei dati per la tabella `logs_actividad`
--

INSERT INTO `logs_actividad` (`id`, `usuario_id`, `accion`, `modulo`, `descripcion`, `fecha`) VALUES
(1, 1, 'login', 'sistema', 'Inició sesión exitosamente', '2026-01-26 23:05:26'),
(2, 1, 'logout', 'sistema', 'Cierre de sesión', '2026-01-26 23:13:00'),
(3, 1, 'login', 'sistema', 'Inició sesión exitosamente', '2026-01-26 23:13:15'),
(4, 1, 'logout', 'sistema', 'Cierre de sesión', '2026-01-26 23:17:00'),
(5, 1, 'login', 'sistema', 'Inició sesión exitosamente', '2026-01-26 23:17:09'),
(6, 1, 'logout', 'sistema', 'Cierre de sesión', '2026-01-26 23:21:52'),
(7, 1, 'login', 'sistema', 'Inició sesión exitosamente', '2026-01-26 23:22:00'),
(8, 1, 'logout', 'sistema', 'Cierre de sesión', '2026-01-26 23:26:27'),
(9, 1, 'login', 'sistema', 'Inició sesión exitosamente', '2026-01-26 23:26:36'),
(10, 1, 'logout', 'sistema', 'Cierre de sesión', '2026-01-26 23:29:57'),
(11, NULL, 'login_fallido', 'sistema', 'Intento fallido para usuario: admin', '2026-01-26 23:30:35'),
(12, 1, 'login', 'sistema', 'Inició sesión exitosamente', '2026-01-26 23:30:45'),
(13, 1, 'logout', 'sistema', 'Cierre de sesión', '2026-01-26 23:30:57'),
(14, NULL, 'login_fallido', 'sistema', 'Intento fallido para usuario: admin', '2026-01-26 23:36:15'),
(15, 1, 'login', 'sistema', 'Inició sesión exitosamente', '2026-01-26 23:38:40'),
(16, 1, 'logout', 'sistema', 'Cierre de sesión', '2026-01-26 23:40:47'),
(17, 1, 'login', 'sistema', 'Inició sesión exitosamente', '2026-01-26 23:40:57'),
(18, 1, 'logout', 'sistema', 'Cierre de sesión', '2026-01-26 23:45:53');

-- --------------------------------------------------------

--
-- Struttura della tabella `prestamos`
--

CREATE TABLE `prestamos` (
  `id` int NOT NULL,
  `id_libro` int NOT NULL,
  `fecha_prestamo` date NOT NULL,
  `fecha_devolucion` date DEFAULT NULL,
  `devuelto` tinyint(1) DEFAULT '0',
  `id_lector` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Struttura della tabella `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `nombre_completo` varchar(100) NOT NULL,
  `activo` tinyint(1) DEFAULT '1',
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `ultimo_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Dump dei dati per la tabella `usuarios`
--

INSERT INTO `usuarios` (`id`, `username`, `password_hash`, `nombre_completo`, `activo`, `fecha_creacion`, `ultimo_login`) VALUES
(1, 'admin', '$2y$10$w6cNC5JuJgxPR5iaEyGD4ujDj2sJRP0GVk3sj6TeBms2OYuSZZIra', 'Encargado de Biblioteca', 1, '2026-01-26 23:00:46', '2026-01-26 23:40:57');

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indici per le tabelle `lectores`
--
ALTER TABLE `lectores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indici per le tabelle `libros`
--
ALTER TABLE `libros`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo_interno` (`codigo_interno`),
  ADD UNIQUE KEY `isbn` (`isbn`);

--
-- Indici per le tabelle `libro_categoria`
--
ALTER TABLE `libro_categoria`
  ADD PRIMARY KEY (`id_libro`,`id_categoria`),
  ADD KEY `id_categoria` (`id_categoria`);

--
-- Indici per le tabelle `logs_actividad`
--
ALTER TABLE `logs_actividad`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_fecha` (`fecha`);

--
-- Indici per le tabelle `prestamos`
--
ALTER TABLE `prestamos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_libro` (`id_libro`),
  ADD KEY `prestamos_ibfk_2` (`id_lector`);

--
-- Indici per le tabelle `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT per le tabelle scaricate
--

--
-- AUTO_INCREMENT per la tabella `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT per la tabella `lectores`
--
ALTER TABLE `lectores`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `libros`
--
ALTER TABLE `libros`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `logs_actividad`
--
ALTER TABLE `logs_actividad`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT per la tabella `prestamos`
--
ALTER TABLE `prestamos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Limiti per le tabelle scaricate
--

--
-- Limiti per la tabella `libro_categoria`
--
ALTER TABLE `libro_categoria`
  ADD CONSTRAINT `libro_categoria_ibfk_1` FOREIGN KEY (`id_libro`) REFERENCES `libros` (`id`),
  ADD CONSTRAINT `libro_categoria_ibfk_2` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id`);

--
-- Limiti per la tabella `prestamos`
--
ALTER TABLE `prestamos`
  ADD CONSTRAINT `prestamos_ibfk_1` FOREIGN KEY (`id_libro`) REFERENCES `libros` (`id`),
  ADD CONSTRAINT `prestamos_ibfk_2` FOREIGN KEY (`id_lector`) REFERENCES `lectores` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
