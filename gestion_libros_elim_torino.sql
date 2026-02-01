-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:8889
-- Creato il: Feb 01, 2026 alle 18:02
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

--
-- Dump dei dati per la tabella `libros`
--

INSERT INTO `libros` (`id`, `codigo_interno`, `titulo`, `autor`, `año_publicacion`, `isbn`, `stock`, `fecha_creacion`, `activo`) VALUES
(1, 'LIB-CO-001', 'Introduzione all\'Antico Testamento', 'Walter Brueggemann', 2005, '978-8-87016-539-5', 1, '2026-02-01 14:13:10', 1),
(2, 'LIB-CO-002', 'Ebrei', 'Thomas G. Long', 2004, '978-8-87016-490-9', 1, '2026-02-01 14:14:49', 1),
(3, 'LIB-CO-003', 'Esodo', 'Terence E. Fretheim', 2004, '978-8-87016-420-6', 1, '2026-02-01 14:16:06', 1),
(4, 'LIB-CO-004', 'Marco', 'Lamar Williamson jr', 2004, '978-8-87016-521-0', 1, '2026-02-01 14:18:09', 1),
(5, 'LIB-CO-005', 'Giobbe', 'J. Gerald Jamzen', 2003, '978-8-87016-517-3', 1, '2026-02-01 14:19:13', 1),
(6, 'LIB-CO-006', 'Isaia', 'Paul D. Hanson', 2006, '978-8-87016-642-2', 1, '2026-02-01 14:20:33', 1),
(7, 'LIB-CO-007', 'Mateo', 'Douglas R.A. Hare', 2006, '978-8-87016-636-1', 1, '2026-02-01 14:22:02', 1),
(8, 'LIB-CO-008', 'Ester', 'Carol M. Bechtel', 2005, '978-8-87016-639-2', 1, '2026-02-01 14:23:47', 1),
(9, 'LIB-CO-009', 'Ezechiele', 'Joseph Blenkinsopp', 2006, '978-8-87016-443-5', 1, '2026-02-01 14:24:58', 1),
(10, 'LIB-CO-010', 'Numeri', 'Dennis T. Olson', 2006, '978-8-87016-620-0', 1, '2026-02-01 14:25:59', 1),
(11, 'LIB-CO-011', 'I Dodici Profeti Parte I', 'James Limburg', 2005, '978-8-87016-591-3', 1, '2026-02-01 14:27:10', 1),
(12, 'LIB-CO-012', 'I-II Samuele', 'Walter Brueggemann', 2005, '978-8-87016-545-6', 1, '2026-02-01 14:29:05', 1),
(13, 'LIB-CO-013', 'Atti Degli Apostoli', 'William H. Willimon', 2003, '978-8-87016-487-9', 1, '2026-02-01 14:30:15', 1),
(14, 'LIB-CO-014', 'Guida allo studio del Nuovo Testamento', 'Marietti (Editorial)', 1990, '978-8-82116-771-3', 1, '2026-02-01 14:33:12', 1),
(15, 'LIB-CO-015', 'Lettera Ai Galati', 'Brunno Corsani', 1990, '978-8-82116-715-7', 1, '2026-02-01 14:35:04', 1),
(16, 'LIB-CO-016', 'Investigare Le Scritture Nuovo Testamento', 'La Casa della Bibbia (Editorial)', 2002, '8-884-69004-8', 1, '2026-02-01 14:39:29', 1),
(17, 'LIB-CO-017', 'Comentario Del Nuevo Testamento', 'William MacDonald', 1995, '978-8-47645-838-9', 1, '2026-02-01 14:44:06', 1),
(18, 'LIB-CO-018', 'Grande Commentario Biblico', 'Queriniana Brescia (Editorial)', 1973, '978-8-83990-054-8', 1, '2026-02-01 14:50:13', 1),
(19, 'LIB-CO-019', 'Signore Insegnaci 1 Timoteo-2 Timoteo-Tito (Commentario Biblico del Nuovo Testamento)', 'J. Ritchie LDT', 2000, '978-1-54954-713-3', 1, '2026-02-01 14:56:59', 1),
(20, 'LIB-CO-020', 'Commentario Biblico Illustrato', 'ADI media (Editorial)', 1995, '8-886-08510-9', 1, '2026-02-01 15:01:10', 1),
(21, 'LIB-CO-021', 'La Lettera Agli Ebrei', 'Harold W. Attriddge', 1999, '978-8-82092-402-7', 1, '2026-02-01 15:03:11', 1),
(22, 'LIB-CO-022', 'Introduzione all\'Antico Testamento', 'Rolf Rendtorff', 2001, '978-8-87016-108-3', 1, '2026-02-01 15:06:58', 1),
(23, 'LIB-CO-023', 'Il Cristianesimo delle Origini', 'François Vouga', 2001, '978-8-87016-380-3', 1, '2026-02-01 15:09:40', 1),
(24, 'LIB-CO-024', 'Genesi', 'Walter Brueggemann', 2002, '978-8-87016-414-5', 1, '2026-02-01 15:11:23', 1),
(25, 'LIB-CO-025', 'Finestre su Gesù', 'Wim Weren', 2001, '978-8-87016-387-2', 1, '2026-02-01 15:22:22', 1),
(26, 'LIB-Vacio', 'LIBRO VACIO', '-', 1990, '1', 1, '2026-02-01 15:26:04', 0),
(29, 'LIB-CO-027', 'Le Epistole Ai Romani I-II Corinzi', 'Enrico Bosio', 1997, 'LIB-CO-027', 1, '2026-02-01 15:28:53', 1),
(30, 'LIB-CO-028', 'Le Epistole Di Paolo (II-Parte)-Commentario Esegetico', 'Enrico Bosio-Giovanni Luzzi', 1990, NULL, 1, '2026-02-01 15:47:07', 1),
(31, 'LIB-CO-029', 'Fatti Degli Apostoli', 'Giovanni Luzzi', 1899, NULL, 1, '2026-02-01 15:55:41', 1),
(32, 'LIB-CO-030', 'L\'Evangelo Secondo Giovanni', 'Robert G. Stewart', 1991, NULL, 1, '2026-02-01 15:57:26', 1),
(33, 'LIB-CO-031', 'L\'Evangelo Secondo Luca', 'Robert G. Stewart', 1991, NULL, 1, '2026-02-01 15:58:22', 1),
(34, 'LIB-CO-032', 'L\'Evangelo Secondo Matteo e Marco', 'Robert G. Stewart', 1988, NULL, 1, '2026-02-01 15:59:59', 1),
(35, 'LIB-CO-033', 'Epistola agli Ebrei-Epistole Cattoliche-Apocalisse', 'Enrico Bosio', 1904, NULL, 1, '2026-02-01 16:01:54', 1),
(36, 'LIB-CO-034', 'Il Nuovo Testamento Annotato (Volume IV) I,II Timoteo-Tito-Filemone-Ebrei-Giacomo-I,II Pietro-I,II, III Giovanni-Giuda-Apocalisse', 'Claudiana (Editorial)', 1966, NULL, 1, '2026-02-01 16:05:57', 1),
(37, 'LIB-CO-035', 'Galati', 'Charles Cousar', 2003, '978-8-87016-441-1', 1, '2026-02-01 16:08:01', 1),
(38, 'LIB-CO-036', 'Luca', 'Fred B. Craddock', 2002, '978-8-87016-432-9', 1, '2026-02-01 16:09:07', 1),
(39, 'LIB-CO-037', 'Introduzione al Nuovo Testamento', 'Daniel Marguerat', 2004, '978-8-87016-453-4', 1, '2026-02-01 16:10:48', 1),
(40, 'LIB-CO-038', 'Da Levitico a Deuteronomio (Volume 2)', 'Matthew Henry', 2002, '978-0-96898-781-0', 1, '2026-02-01 16:14:14', 1),
(41, 'LIB-CO-039', 'L\'Epistola Di Paolo ai Romani', 'Frederick F. Bruce', 1977, NULL, 1, '2026-02-01 16:19:27', 1),
(42, 'LIB-CO-040', 'Signore Insegnaci (1-2 Tessalonicesi)', 'J. Ritchie LDT', 1997, NULL, 1, '2026-02-01 16:21:35', 1),
(43, 'LIB-CO-041', 'Signore Insegnaci (1-2 Corinzi)', 'J. Ritchie LDT', 2002, NULL, 1, '2026-02-01 16:23:13', 1),
(44, 'LIB-CO-042', 'Da Giosuè a II Samuele (Volume 3)', 'Matthew Henry', 2003, '978-0-96898-782-7', 1, '2026-02-01 16:25:08', 1),
(45, 'LIB-CO-043', 'Genesi-Esodo', 'Matthew Henry', 1972, '978-0-96898-780-3', 1, '2026-02-01 16:26:51', 1),
(46, 'LIB-CO-044', 'Le Epistole Di Giovanni (Nuova Edizione)', 'John Stott', 1950, '978-8-88827-036-4', 1, '2026-02-01 16:29:19', 1),
(47, 'LIB-CO-045', 'Le Espistole Di Giovanni', 'Jhon Stott', 1972, NULL, 1, '2026-02-01 16:32:52', 1),
(48, 'LIB-CO-046', 'Il Vangelo Secondo Marco', 'R. Alan Cole', 1998, '8-888-27008-6', 1, '2026-02-01 16:34:08', 1),
(49, 'LIB-CO-047', 'Il Vangelo Secondo Matteo', 'R.T. France', 2004, '978-8-88827-067-8', 1, '2026-02-01 16:35:36', 1),
(50, 'LIB-CO-048', 'Il Vangelo Secondo Luca', 'Leon Morris', 2003, '978-8-88827-063-0', 1, '2026-02-01 16:36:50', 1),
(51, 'LIB-CO-049', 'Signore Insegnaci (Giovanni)', 'J. Ritchie LDT', 1999, NULL, 1, '2026-02-01 16:38:13', 1),
(52, 'LIB-CO-050', 'Investigare le Scritture Antico Testamento', 'La casa della Bibbia (Editorial)', 2001, NULL, 1, '2026-02-01 16:39:37', 1),
(53, 'LIB-CO-051', 'I Quattro Vangeli', 'Angelico Poppi', 1997, '978-8-82500-662-9', 1, '2026-02-01 16:40:53', 1),
(54, 'LIB-CO-052', 'Il Nuovo Testamento Annotato (Volume I) i Vangeli sinottici', 'Claudiana (Editorial)', 1965, NULL, 1, '2026-02-01 16:48:31', 1),
(55, 'LIB-CO-053', 'Il Nuovo Testamento Annotato (Volume II) Vangelo Secondo Giovanni-Atti Degli Apostoli', 'Claudiana (Editorial)', 1968, NULL, 1, '2026-02-01 16:49:56', 1),
(56, 'LIB-CO-054', 'Il Nuovo Testamento Annotato (Volume III) Le Epistole di Paolo', 'Claudiana (Editorial)', 1974, NULL, 1, '2026-02-01 16:51:11', 1),
(57, 'LIB-CO-055', 'Studia La Parola-Una Guida allo Studio Della Bibbia (Volume I)', 'GBU Roma(Editorial)-Autori Vari', 2000, NULL, 1, '2026-02-01 16:53:27', 1),
(58, 'LIB-CO-056', 'Studia La Parola-Una Guida allo Studio Della Bibbia (Volume II)', 'GBU Roma(Editorial)-Autori Vari', 2000, NULL, 1, '2026-02-01 16:54:47', 1),
(59, 'LIB-CO-057', 'Introduzione alla Lettura della Bibbia (L\'antico Testamento)', 'H.E Alexander', 1972, NULL, 1, '2026-02-01 16:57:07', 1),
(60, 'LIB-CO-058', 'Il Libro del Deuteronomio', 'Alfredo Apicella', 1998, NULL, 1, '2026-02-01 16:58:24', 1),
(61, 'LIB-CO-059', 'Commentario Biblico Moody (Antiguo Testamento)', 'Charles F. Pfeiffer', 1993, '978-0-82541-562-3', 1, '2026-02-01 16:59:59', 1),
(62, 'LIB-CO-060', 'Guida Allo Studio Del Greco del Nuovo Testamento', 'Bruno Corsani', 2000, '978-8-82378-002-6', 1, '2026-02-01 17:05:28', 1),
(63, 'LIB-CO-061', 'Guida allo Studio Della Bibbia Greca (LXX)', 'Mario Cimosa', 1995, NULL, 1, '2026-02-01 17:07:22', 1),
(64, 'LIB-CO-062', 'Guida allo Studio Dell\'Ebraico Biblico', 'Giovanni Deina-Ambrogio Spreafico', 2000, '978-8-82378-005-7', 1, '2026-02-01 17:09:30', 1),
(65, 'LIB-CH-001', 'Lessico Dei Termini Biblici', 'Bernard Gillieron', 2000, '978-8-80113-578-7', 1, '2026-02-01 17:24:05', 1),
(66, 'LIB-CH-002', 'Guida Allo Studio Dell\'Ebraico Biblico (Parte Pratica)', 'Giovanni Deiana-Ambroggio Spreafico', 2001, '978-8-82378-006-4', 1, '2026-02-01 17:25:52', 1),
(67, 'LIB-CH-003', 'Per leggere L\'antico Testamento', 'Etienne Charpentier', 1999, '978-8-82630-340-6', 1, '2026-02-01 17:27:16', 1),
(68, 'LIB-CH-004', 'Alle Radici Della Fede', 'Paolo Ricca', 1987, NULL, 1, '2026-02-01 17:28:52', 1),
(69, 'LIB-CH-005', 'Israele in epoca Biblica', 'J. Alberto Soggin', 2000, '978-8-87016-358-2', 1, '2026-02-01 17:30:30', 1),
(70, 'LIB-CH-006', 'Introduzione alla letteratura mediogiudaica precristiana', 'Eric Noffke', 2004, '978-8-87016-452-7', 1, '2026-02-01 17:31:49', 1),
(71, 'LIB-CH-007', 'Storia del popolo Giudaico al tempo di Gesù Cristo (Volume I)', 'Emil Schürer', 1985, '978-8-83940-184-7', 1, '2026-02-01 17:33:28', 1),
(72, 'LIB-CH-008', 'Storia del popolo Giudaico al tempo di Gesù Cristo (Volume II)', 'Emil Schürer', 1987, '978-8-83940-397-1', 1, '2026-02-01 17:36:00', 1),
(73, 'LIB-CH-009', 'Storia del popolo Giudaico al tempo di Gesù Cristo (Volume III Tomo Primo)', 'Emil Schürer', 1997, '978-8-83940-549-4', 1, '2026-02-01 17:38:25', 1),
(74, 'LIB-CH-010', 'Storia del popolo Giudaico al tempo di Gesù Cristo (Volume III Tomo Secondo)', 'Emil Schürer', 1998, NULL, 1, '2026-02-01 17:39:35', 1),
(75, 'LIB-DIC-001', 'Esercizi per il Corso di Greco del Nuovo Testamento', 'Flaminio Poggi-Filippo Serafini', 2003, '978-8-82154-967-0', 1, '2026-02-01 13:16:45', 1),
(76, 'LIB-DIC-002', 'Corso di Greco del Nuovo Testamento', 'Filippo Serafini', 2003, '978-8-82154-966-3', 1, '2026-02-01 13:18:45', 1),
(77, 'LIB-DIC-003', 'Lessico Raggionato della Antichita Classica', 'Federico Lübker', 1989, NULL, 1, '2026-02-01 13:20:43', 1),
(78, 'LIB-DIC-004', 'Dizionario dei Concetti Biblici del Nuovo Testamento', 'L.Coenen - E.Beyreuther- H.Bietnhard', 2000, '978-8-81020-519-8', 1, '2026-02-01 13:25:15', 1),
(79, 'LIB-DIC-005', 'Diccionario Griego-Español', 'F.Sanz Franco', 1995, NULL, 1, '2026-02-01 13:27:03', 1),
(80, 'LIB-DIC-006', 'Dizionario Biblico', 'Giovanni Miegge', NULL, '8-870-16013-0', 1, '2026-02-01 13:29:06', 1),
(81, 'LIB-DIC-007', 'Lexico Griego-Español del Nuevo Testamento', 'Alfred E.Tuggy', NULL, '0-311-03644-9', 1, '2026-02-01 13:31:14', 1),
(82, 'LIB-TEO-001', 'Teologia del Nuevo Testamento', 'Francois Vouga', 2007, '978-8-87016-634-7', 1, '2026-02-01 13:34:44', 1),
(83, 'LIB-TEO-002', 'Il Cristo -Volume I- Testi Teologici e Spirituali dal I al IV Secolo', 'Antonio Orbe -Manlio Simoneti', NULL, NULL, 1, '2026-02-01 13:36:23', 1),
(84, 'LIB-TEO-003', 'Il Cristo- Volume II - Testi Teologici e Spirituali in Lingua Greca Dal IV al VII Secolo', 'Manlio Simonetti', 1990, '9788804269885', 1, '2026-02-01 13:42:10', 1),
(85, 'LIB-TEO-004', 'Possibilita di Dio nella Realta del Mondo', 'Eberhard Jüngel', 2005, '9788870164473', 1, '2026-02-01 13:44:36', 1),
(86, 'LIB-TEO-005', 'Il Dio  Di Gesu Il Gesu storico e la ricerca del significato', 'Stephen J. Patterson', 2005, '9788870165258', 1, '2026-02-01 13:46:09', 1),
(87, 'LIB-TEO-006', 'La Natura della Dottrina Religione e Teologia Religione e teologia in un\'epoca postliberale', 'George A. Lindbeck', 2004, '9788870164626', 1, '2026-02-01 13:48:20', 1),
(88, 'LIB-TEO-007', 'Scienza e fede in Dialogo I fondamenti', 'Alister E. McGrath', 2002, '9788870164053', 1, '2026-02-01 13:50:22', 1),
(89, 'LIB-TEO-008', 'Teologia cristina', 'Alister E. McGrath', 1999, '978-8-87016-310-0', 1, '2026-02-01 13:51:28', 1),
(90, 'LIB-TEO-009', 'Teologia dell\'Antico Testamento -Volume I:Testi canonici', 'Rolf Rendtorff', 2001, '978-8-87016-365-0', 1, '2026-02-01 13:53:34', 1),
(91, 'LIB-TEO-010', 'Teologia Biblica- Antico e Nuovo Testamante', 'Breward S. Childs', 1998, '978-8-83843-098-5', 1, '2026-02-01 13:55:05', 1),
(92, 'LIB-TEO-011', 'Teologia dell\'Antico Testamento -Volume II: I temi', 'Rolf Rendtorff', 2003, '978-8-87016-449-7', 1, '2026-02-01 13:56:27', 1),
(93, 'LIB-TEO-012', 'Grammatica Greca del Nuovo Testamento', 'Eric G.Jay', 2001, '978-8-83841-762-7', 1, '2026-02-01 13:58:25', 1),
(94, 'LIB-TEO-013', 'Introduzione Generale alla Bibbia', 'Michelangelo Tabet', 1998, '978-8-82153-570-3', 1, '2026-02-01 13:59:58', 1),
(95, 'LIB-TEO-014', 'Introduzione Generale alle Bibbia', 'Rinaldo Fabris e collaboratori', 2001, '978-8-80110-334-2', 1, '2026-02-01 14:01:30', 1),
(96, 'LIB-TEO-015', 'Dalla \"Riscoperta de Dio\" all\'impegno nella societa', 'Giovanni Miegge', 1977, NULL, 1, '2026-02-01 14:04:53', 1),
(97, 'LIB-TEO-016', 'Al pricipio , la Grazia', 'Giovanni Miegge', 1997, NULL, 1, '2026-02-01 14:06:37', 1),
(98, 'LIB-TEO-017', 'Teologia elementare', 'E.H. Bancroft', 1977, NULL, 1, '2026-02-01 14:07:42', 1),
(99, 'LIB-TEO-018', 'Cristologia', 'Olegario González de Cardenal', 2004, '978-8-82155-028-7', 1, '2026-02-01 14:08:45', 1),
(100, 'LIB-OBI-001', 'Risurrezione', 'N.T.wright', 2006, '978-8-87016-547-0', 1, '2026-02-01 14:13:11', 1),
(101, 'LIB-OBI-002', 'Leggere e Capire la Bibia', 'John H. Alexander', 1971, NULL, 1, '2026-02-01 14:14:43', 1),
(102, 'LIB-OBI-003', 'Spiritualita Cristiana', 'Alister E. McGrath', 2002, '978-8-87016-429-9', 1, '2026-02-01 14:15:37', 1),
(103, 'LIB-OBI-004', 'Nuove Evidenze Che richiedono un verdetto', 'Josh McDowell', 1999, '987-8-87054-202-6', 1, '2026-02-01 14:16:55', 1),
(104, 'LIB-OBI-005', 'Il Giusto Giudizio di Dio', 'D. Martyn Lloyd-Lones', 1997, NULL, 1, '2026-02-01 14:18:29', 1),
(105, 'LIB-OBI-006', 'Le Istituzione del Antico Testamento', 'R. De Vaux', 1977, '978-8-82117-143-7', 1, '2026-02-01 14:19:51', 1),
(106, 'LIB-OBI-007', 'Le Due Missioni - Pietro e Paolo', 'Michael Goulder', 2006, '978-8-87016-484-8', 1, '2026-02-01 14:21:35', 1),
(107, 'LIB-OBI-008', 'I predestinati Religioni e Reliogione nel Protestantesimo', 'Sergio Rostagno', 2006, '978-8-87016-618-7', 1, '2026-02-01 14:24:10', 1),
(108, 'LIB-OBI-009', 'Cristo contro Cesare: Come gli ebrei e i cristiani del I secolo risposero alla sfida dell\'Imperialismo Romano', 'Eric Noffke', 2006, '978-8-87016-598-2', 1, '2026-02-01 14:26:01', 1),
(109, 'LIB-OBI-010', 'Qohelet ovvero il dubbio radicale', 'Elsa Tamez', 2005, '978-8-87016-513-5', 1, '2026-02-01 14:27:31', 1),
(110, 'LIB-OBI-011', 'Sofferenza: Alla ricerca di una risposta', 'Frederick W. Schmidt jr.', 2004, '978-8-87016-440-4', 1, '2026-02-01 14:30:33', 1),
(111, 'LIB-OBI-012', 'Gesú di Nazareth: Sfide e provocazioni', 'N.T. Wright', 2003, '978-8-87016-451-0', 1, '2026-02-01 14:31:48', 1),
(112, 'LIB-OBI-013', 'Cristo in Tutte le Scritture', 'A. M. Hodgkin', 2004, NULL, 1, '2026-02-01 14:34:10', 1),
(113, 'LIB-OBI-014', 'Per Leggere il Nuovo Testamento', 'Etienne Charpentier', 1998, '978-8-82630-341-3', 1, '2026-02-01 14:36:54', 1),
(114, 'LIB-OBI-015', 'Fede Cristiana e Scienza', 'Pietro Bolognesi', 1979, NULL, 1, '2026-02-01 14:39:26', 1),
(115, 'LIB-OBI-016', 'Personaggi della bibbia', 'Gianfranco Ravasi', 2006, '977-1-82445-836-0', 1, '2026-02-01 14:41:38', 1),
(116, 'LIB-HIS-001', 'Cristianesimo: Origini e Diffusione in Tutte le sue Forme', 'Mondadori', 2005, '977-0-03815-621-5', 1, '2026-02-01 14:54:48', 1),
(117, 'LIB-HIS-002', 'Cristianesimo atraverso i secoli', 'Earle E. Cairns', 1970, NULL, 1, '2026-02-01 14:56:32', 1),
(118, 'LIB-HIS-003', 'La Croce e la Mezza Luna - Lepanto 7 Ottobre 1571: Quando la Cristianitá rispinsi l\'Islam', 'Arrigo Petaco', 2005, '978-8-80454-397-8', 1, '2026-02-01 14:58:41', 1),
(119, 'LIB-HIS-004', 'I Papi: Storia e Segreti', 'Claudio Rendina', 1983, '978-8-88289-070-4', 1, '2026-02-01 15:00:44', 1),
(120, 'LIB-HIS-005', 'Il Pensiero della Riforma', 'Alister E. McGrath', 1999, '979-8-87016-146-4', 1, '2026-02-01 15:02:09', 1),
(121, 'LIB-HIS-006', 'Archeologia e Bibbia: Seconda Parte Nuovo Testamento', 'Davide Valente', 2001, NULL, 1, '2026-02-01 15:04:22', 1),
(122, 'LIB-HIS-007', 'I Manoscritti del Mar Morto', 'Stephen Hodge', 2002, '9788882896942', 1, '2026-02-01 15:05:55', 1),
(123, 'LIB-HIS-008', 'L\'Universale: Religioni', 'Gerhard J. Bellinger', 2004, NULL, 1, '2026-02-01 15:12:56', 1),
(124, 'LIB-HIS-009', 'La Religione dei Primi Cristiani', 'Gerd Theissen', 2004, '978-8-87016-454-1', 1, '2026-02-01 15:15:15', 1),
(125, 'LIB-HIS-010', 'Storia dell\'Annabattismo', 'Ugo Gastaldi', 1992, NULL, 1, '2026-02-01 15:18:03', 1),
(126, 'LIB-DIC-008', 'Nuovo Dizionario Biblico', 'René Pache', 2004, NULL, 1, '2026-02-01 15:20:58', 1),
(127, 'LIB-OBM-001', 'La Filosofia dei Grechi nel suo Sviluppo Storico - Parte Terza Vol. VI', 'E. Zeller - R. Mondolfo', 1961, NULL, 1, '2026-02-01 15:25:03', 1),
(128, 'LIB-OBM-002', 'Vita Senza Limiti - Per una vita assurdamente felice', 'Nick Vujicic', 2017, '978-8-88469-064-7', 1, '2026-02-01 15:27:27', 1),
(129, 'LIB-OBM-003', 'Come Ha Avuto Origene la Vita - Per evoluzione o per creazione?', 'Testimoni di Geova', 1988, NULL, 1, '2026-02-01 15:29:43', 1),
(130, 'LIB-OBM-004', 'La Rabbia e L\'Orgoglio - La Trilogia di Oriana Fallaci: Libro 1', 'Oriana Fallaci', 2001, NULL, 1, '2026-02-01 15:31:33', 1),
(131, 'LIB-OBM-005', 'La Forza della Ragione - La Trilogia di Oriana Fallaci: Libro 2', 'Oriana Fallaci', 2004, NULL, 1, '2026-02-01 15:34:35', 1),
(132, 'LIB-OBM-006', 'Oriana Fallici intervista sé stessa - L\'Apocalisse - Trilogia di Oriana Fallici: Libro 3', 'Oriana Fallaci', 2004, NULL, 1, '2026-02-01 15:35:59', 1),
(133, 'LIB-OBM-007', 'Tehillim Salmi - Un canto ogni giorno, un per ogni giorno', 'Carlo Maria Martini', 2000, '978-8-87152-558-7', 1, '2026-02-01 15:38:06', 1),
(134, 'LIB-OBM-008', 'Rivelazione - Il suo grandiozo culmine é vicino!', 'Testimoni di Geova', 1988, NULL, 1, '2026-02-01 15:39:34', 1),
(135, 'LIB-OBM-009', 'La Filosofia dei Grechi nel suo Sviluppo Storico - Parte Prima Vol. I', 'E. Zeller - R. Mondolfo', 1967, NULL, 1, '2026-02-01 15:40:53', 1),
(136, 'LIB-OBM-010', 'La Filosofia dei Grechi nel suo Sviluppo Storico - Parte Prima Vol. IV', 'E. Zeller - R. Mondolfo', 1968, NULL, 1, '2026-02-01 15:42:27', 1),
(137, 'LIB-OBM-011', 'La Filosofia dei Grechi nel suo Sviluppo Storico - Parte Prima Vol. V', 'E. Zeller - R. Mondolfo', 1969, NULL, 1, '2026-02-01 15:48:04', 1),
(138, 'LIB-OBM-012', 'La Filosofia dei Grechi nel suo Sviluppo Storico - Parte Prima Vol. II', 'E. Zeller - R. Mondolfo', 1967, NULL, 1, '2026-02-01 15:48:37', 1),
(139, 'LIB-OBM-013', 'La Filosofia dei Grechi nel suo Sviluppo Storico - Parte Prima Vol. III', 'E. Zeller - R. Mondolfo', 1967, NULL, 1, '2026-02-01 15:49:23', 1),
(140, 'LIB-OBM-014', 'Dizionario di Filosofia', 'Dagobert D. Runes', 1972, NULL, 1, '2026-02-01 15:50:17', 1),
(141, 'LIB-OBM-015', 'La Straordinaria Storia della Vita - Dalle prime molecole organiche all\'uomo d\'oggi', 'Piero e Alberto Angela', 1999, '977-1-12639-000-9', 1, '2026-02-01 15:52:59', 1),
(142, 'LIB-OBM-016', 'Famiglia e Socializzazione', 'Talcott Parsons - Robert F. Bales', 1955, NULL, 1, '2026-02-01 15:54:05', 1),
(143, 'LIB-OBM-017', 'Sociologia: Uomo, Famiglia e Societá', 'Autori Vari: Il Libro del Mondo S.p.A', 1971, NULL, 1, '2026-02-01 15:55:48', 1),
(144, 'LIB-OBM-018', 'Comunicazioni: Parola, Visione e Informazione', 'Autori Vari: Il Libro del Mondo S.p.A', 1971, NULL, 1, '2026-02-01 15:56:57', 1),
(145, 'LIB-OBM-019', 'Arte: Idea, Creazione e Forma', 'Autori Vari: Il Libro del Mondo S.p.A', 1971, NULL, 1, '2026-02-01 15:57:44', 1),
(146, 'LIB-OBM-020', 'La Musica - Parte Prima: Enciclopedia Storica', 'Guido M. Gatti', 1966, NULL, 1, '2026-02-01 15:59:12', 1),
(147, 'LIB-BIB-001', 'Nuovo Testamento Greco-Italiano', 'Nestle Aland', 1996, '978-8-82372-068-8', 1, '2026-02-01 16:02:35', 1),
(148, 'LIB-DIC-009', 'Dizionario Base del Nuovo Testamento Greco-Italiano', 'Carlo Buzzetti', 2001, '978-8-82378-030-9', 1, '2026-02-01 16:04:54', 1),
(149, 'LIB-BIB-002', 'La Sacra Bibbia - Traduzione dai Testi Originali', 'Autori Vari - Pia Societá San Paolo', 1964, NULL, 1, '2026-02-01 16:06:49', 1),
(150, 'LIB-BIB-003', 'La Bibbia - Nuova Riveduta', 'Societá Biblica di Ginevra', 2009, '9782608363015', 1, '2026-02-01 16:07:24', 1),
(151, 'LIB-BIB-004', 'Sinossi Quadriforme dei Quattro Vangeli Greco-Italiano Vol. I', 'Angelico Poppi', 1999, NULL, 1, '2026-02-01 16:10:43', 1),
(152, 'LIB-BIB-005', 'Traduzione del Nuovo Mondo delle Sacre Scritture', 'Testimoni di Geova', 1987, NULL, 1, '2026-02-01 16:12:04', 1),
(153, 'LIB-CON-001', 'La Nuova Concordanza Biblica', 'Ruben A. Torrey', 1998, '978-8-88608-534-2', 1, '2026-02-01 16:17:23', 1),
(154, 'LIB-CON-002', 'Vita Pratica - Attraverso il Nuovo Testamento - Nuova Riveduta', 'Edizione Centro Biblico', 2003, '987-8-87054-201-9', 1, '2026-02-01 16:21:34', 1),
(155, 'LIB-CON-003', 'Chiave Biblica - Versione la Nuova Diodati', 'Edizione La Buona Novella - Brindisi', 1995, NULL, 1, '2026-02-01 16:23:45', 1),
(156, 'LIB-CON-004', 'La Nuova Chiave Biblica - Compilata sulla Bibbia Versione Nuova Riveduta', 'ADI - MEDIA', 2000, '978-8-88608-562-5', 1, '2026-02-01 16:25:24', 1),
(157, 'LIB-CON-005', 'Chiave Biblicaa ossia Concordanza della Sacra Scrittura compilata sulla Versione Riveduta', 'Editrice Claudiana - Torino', 1981, NULL, 1, '2026-02-01 16:27:33', 1);

-- --------------------------------------------------------

--
-- Struttura della tabella `libro_categoria`
--

CREATE TABLE `libro_categoria` (
  `id_libro` int NOT NULL,
  `id_categoria` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Dump dei dati per la tabella `libro_categoria`
--

INSERT INTO `libro_categoria` (`id_libro`, `id_categoria`) VALUES
(1, 3),
(2, 3),
(3, 3),
(4, 3),
(5, 3),
(6, 3),
(7, 3),
(8, 3),
(9, 3),
(10, 3),
(11, 3),
(12, 3),
(13, 3),
(14, 3),
(15, 3),
(16, 3),
(17, 3),
(18, 3),
(19, 3),
(20, 3),
(21, 3),
(22, 3),
(23, 3),
(24, 3),
(25, 3),
(26, 3),
(29, 3),
(30, 3),
(31, 3),
(32, 3),
(33, 3),
(34, 3),
(35, 3),
(36, 3),
(37, 3),
(38, 3),
(39, 3),
(40, 3),
(41, 3),
(42, 3),
(43, 3),
(44, 3),
(45, 3),
(46, 3),
(47, 3),
(48, 3),
(49, 3),
(50, 3),
(51, 3),
(52, 3),
(53, 3),
(54, 3),
(55, 3),
(56, 3),
(57, 3),
(58, 3),
(59, 3),
(60, 3),
(61, 3),
(62, 3),
(63, 3),
(64, 3),
(65, 7),
(66, 7),
(67, 7),
(68, 7),
(69, 7),
(70, 7),
(71, 7),
(72, 7),
(73, 7),
(74, 7);

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
(18, 1, 'logout', 'sistema', 'Cierre de sesión', '2026-01-26 23:45:53'),
(19, 1, 'login', 'sistema', 'Inició sesión exitosamente', '2026-02-01 13:59:49'),
(20, 1, 'acceso', 'prestamos', 'Accedió al formulario de nuevo préstamo', '2026-02-01 13:59:54'),
(21, 1, 'acceso', 'catalogo', 'Accedió al catálogo de libros', '2026-02-01 13:59:58'),
(22, 1, 'consulta_catalogo', 'catalogo', 'Consultando catálogo - Total libros: 0, Página 1/0', '2026-02-01 13:59:58'),
(23, 1, 'acceso', 'catalogo', 'Accedió al catálogo de libros', '2026-02-01 15:23:49'),
(24, 1, 'consulta_catalogo', 'catalogo', 'Consultando catálogo - Total libros: 25, Página 1/2', '2026-02-01 15:23:49'),
(25, 1, 'acceso_edicion', 'catalogo', 'Accedió a editar libro', '2026-02-01 15:24:09'),
(26, 1, 'consulta_libro', 'catalogo', 'Consultando datos del libro ID: 24', '2026-02-01 15:24:09'),
(27, 1, 'libro_encontrado', 'catalogo', 'Editando libro - ID: 24, Título: \'Genesi\', Código: LIB-CO-24', '2026-02-01 15:24:09'),
(28, 1, 'estadisticas_prestamos', 'catalogo', 'Estadísticas préstamos libro ID: 24 - Total: 0, Activos: ', '2026-02-01 15:24:09'),
(29, 1, 'acceso_edicion', 'catalogo', 'Accedió a editar libro', '2026-02-01 15:24:14'),
(30, 1, 'consulta_libro', 'catalogo', 'Consultando datos del libro ID: 24', '2026-02-01 15:24:14'),
(31, 1, 'libro_encontrado', 'catalogo', 'Editando libro - ID: 24, Título: \'Genesi\', Código: LIB-CO-24', '2026-02-01 15:24:14'),
(32, 1, 'inicio_actualizacion', 'catalogo', 'Iniciando actualización del libro ID: 24', '2026-02-01 15:24:14'),
(33, 1, 'inicio_transaccion', 'catalogo', 'Iniciando transacción para libro ID: 24', '2026-02-01 15:24:14'),
(34, 1, 'libro_actualizado_bd', 'catalogo', 'Libro actualizado en BD - ID: 24', '2026-02-01 15:24:14'),
(35, 1, 'categorias_eliminadas', 'catalogo', 'Categorías anteriores eliminadas para libro ID: 24', '2026-02-01 15:24:14'),
(36, 1, 'categorias_asignadas', 'catalogo', '1 categorías asignadas a libro ID: 24', '2026-02-01 15:24:14'),
(37, 1, 'transaccion_exitosa', 'catalogo', 'Transacción completada exitosamente para libro ID: 24', '2026-02-01 15:24:14'),
(38, 1, 'cambios_detallados', 'catalogo', 'Cambios en libro ID: 24 - código: \'LIB-CO-24\' → \'LIB-CO-024\'', '2026-02-01 15:24:14'),
(39, 1, 'estadisticas_prestamos', 'catalogo', 'Estadísticas préstamos libro ID: 24 - Total: 0, Activos: ', '2026-02-01 15:24:14'),
(40, 1, 'acceso', 'catalogo', 'Accedió al catálogo de libros', '2026-02-01 15:24:16'),
(41, 1, 'consulta_catalogo', 'catalogo', 'Consultando catálogo - Total libros: 25, Página 1/2', '2026-02-01 15:24:16'),
(42, 1, 'acceso', 'catalogo', 'Accedió al catálogo de libros', '2026-02-01 15:28:54'),
(43, 1, 'consulta_catalogo', 'catalogo', 'Consultando catálogo - Total libros: 27, Página 1/2', '2026-02-01 15:28:54'),
(44, 1, 'acceso', 'catalogo', 'Accedió al catálogo de libros', '2026-02-01 15:28:56'),
(45, 1, 'consulta_catalogo', 'catalogo', 'Consultando catálogo - Total libros: 27, Página 2/2', '2026-02-01 15:28:56'),
(46, 1, 'acceso', 'catalogo', 'Accedió al catálogo de libros', '2026-02-01 15:30:59'),
(47, 1, 'consulta_catalogo', 'catalogo', 'Consultando catálogo - Total libros: 27, Página 2/2', '2026-02-01 15:30:59'),
(48, 1, 'acceso', 'prestamos', 'Accedió al formulario de nuevo préstamo', '2026-02-01 15:31:02'),
(49, 1, 'acceso', 'catalogo', 'Accedió al catálogo de libros', '2026-02-01 15:31:37'),
(50, 1, 'consulta_catalogo', 'catalogo', 'Consultando catálogo - Total libros: 27, Página 1/2', '2026-02-01 15:31:37'),
(51, 1, 'acceso', 'catalogo', 'Accedió al catálogo de libros', '2026-02-01 15:31:45'),
(52, 1, 'consulta_catalogo', 'catalogo', 'Consultando catálogo - Total libros: 27, Página 2/2', '2026-02-01 15:31:45'),
(53, 1, 'acceso_edicion', 'catalogo', 'Accedió a editar libro', '2026-02-01 15:32:03'),
(54, 1, 'consulta_libro', 'catalogo', 'Consultando datos del libro ID: 29', '2026-02-01 15:32:03'),
(55, 1, 'libro_encontrado', 'catalogo', 'Editando libro - ID: 29, Título: \'Le Epistole Ai Romani I-II Corinzi\', Código: LIB-CO-027', '2026-02-01 15:32:03'),
(56, 1, 'estadisticas_prestamos', 'catalogo', 'Estadísticas préstamos libro ID: 29 - Total: 0, Activos: ', '2026-02-01 15:32:03'),
(57, 1, 'acceso_edicion', 'catalogo', 'Accedió a editar libro', '2026-02-01 15:32:07'),
(58, 1, 'consulta_libro', 'catalogo', 'Consultando datos del libro ID: 29', '2026-02-01 15:32:07'),
(59, 1, 'libro_encontrado', 'catalogo', 'Editando libro - ID: 29, Título: \'Le Epistole Ai Romani I-II Corinzi\', Código: LIB-CO-027', '2026-02-01 15:32:07'),
(60, 1, 'inicio_actualizacion', 'catalogo', 'Iniciando actualización del libro ID: 29', '2026-02-01 15:32:07'),
(61, 1, 'inicio_transaccion', 'catalogo', 'Iniciando transacción para libro ID: 29', '2026-02-01 15:32:07'),
(62, 1, 'error_transaccion', 'catalogo', 'Error en transacción - Libro ID: 29, Mensaje: Duplicate entry \'\' for key \'libros.isbn\'', '2026-02-01 15:32:07'),
(63, 1, 'estadisticas_prestamos', 'catalogo', 'Estadísticas préstamos libro ID: 29 - Total: 0, Activos: ', '2026-02-01 15:32:07'),
(64, 1, 'acceso_edicion', 'catalogo', 'Accedió a editar libro', '2026-02-01 15:32:27'),
(65, 1, 'consulta_libro', 'catalogo', 'Consultando datos del libro ID: 29', '2026-02-01 15:32:27'),
(66, 1, 'libro_encontrado', 'catalogo', 'Editando libro - ID: 29, Título: \'Le Epistole Ai Romani I-II Corinzi\', Código: LIB-CO-027', '2026-02-01 15:32:27'),
(67, 1, 'inicio_actualizacion', 'catalogo', 'Iniciando actualización del libro ID: 29', '2026-02-01 15:32:27'),
(68, 1, 'inicio_transaccion', 'catalogo', 'Iniciando transacción para libro ID: 29', '2026-02-01 15:32:27'),
(69, 1, 'error_transaccion', 'catalogo', 'Error en transacción - Libro ID: 29, Mensaje: Duplicate entry \'\' for key \'libros.isbn\'', '2026-02-01 15:32:27'),
(70, 1, 'estadisticas_prestamos', 'catalogo', 'Estadísticas préstamos libro ID: 29 - Total: 0, Activos: ', '2026-02-01 15:32:27'),
(71, 1, 'acceso', 'catalogo', 'Accedió al catálogo de libros', '2026-02-01 15:32:44'),
(72, 1, 'consulta_catalogo', 'catalogo', 'Consultando catálogo - Total libros: 27, Página 1/2', '2026-02-01 15:32:44'),
(73, 1, 'acceso', 'catalogo', 'Accedió al catálogo de libros', '2026-02-01 15:32:47'),
(74, 1, 'consulta_catalogo', 'catalogo', 'Consultando catálogo - Total libros: 27, Página 2/2', '2026-02-01 15:32:47'),
(75, 1, 'acceso', 'catalogo', 'Accedió al catálogo de libros', '2026-02-01 15:33:59'),
(76, 1, 'intento_eliminar', 'catalogo', 'Intentando eliminar libro ID: 26', '2026-02-01 15:33:59'),
(77, 1, 'eliminacion_exitosa', 'catalogo', 'Libro eliminado/archivado ID: 26 - Título: \'Le Epistole Di Paolo\', Código: LIB-CO-026', '2026-02-01 15:33:59'),
(78, 1, 'consulta_catalogo', 'catalogo', 'Consultando catálogo - Total libros: 26, Página 2/2', '2026-02-01 15:33:59'),
(79, 1, 'acceso', 'catalogo', 'Accedió al catálogo de libros', '2026-02-01 15:35:11'),
(80, 1, 'intento_eliminar', 'catalogo', 'Intentando eliminar libro ID: 26', '2026-02-01 15:35:11'),
(81, 1, 'error_eliminacion_bd', 'catalogo', 'Error al eliminar libro ID: 26', '2026-02-01 15:35:11'),
(82, 1, 'consulta_catalogo', 'catalogo', 'Consultando catálogo - Total libros: 26, Página 2/2', '2026-02-01 15:35:11'),
(83, 1, 'acceso', 'catalogo', 'Accedió al catálogo de libros', '2026-02-01 15:35:14'),
(84, 1, 'intento_eliminar', 'catalogo', 'Intentando eliminar libro ID: 26', '2026-02-01 15:35:14'),
(85, 1, 'error_eliminacion_bd', 'catalogo', 'Error al eliminar libro ID: 26', '2026-02-01 15:35:14'),
(86, 1, 'consulta_catalogo', 'catalogo', 'Consultando catálogo - Total libros: 26, Página 2/2', '2026-02-01 15:35:14'),
(87, 1, 'acceso', 'catalogo', 'Accedió al catálogo de libros', '2026-02-01 15:35:32'),
(88, 1, 'intento_eliminar', 'catalogo', 'Intentando eliminar libro ID: 26', '2026-02-01 15:35:32'),
(89, 1, 'eliminacion_exitosa', 'catalogo', 'Libro eliminado/archivado ID: 26 - Título: \'Le Epistole Di Paolo\', Código: LIB-CO-026', '2026-02-01 15:35:32'),
(90, 1, 'consulta_catalogo', 'catalogo', 'Consultando catálogo - Total libros: 26, Página 2/2', '2026-02-01 15:35:32'),
(91, 1, 'acceso', 'catalogo', 'Accedió al catálogo de libros', '2026-02-01 15:35:37'),
(92, 1, 'intento_eliminar', 'catalogo', 'Intentando eliminar libro ID: 26', '2026-02-01 15:35:37'),
(93, 1, 'error_eliminacion_bd', 'catalogo', 'Error al eliminar libro ID: 26', '2026-02-01 15:35:37'),
(94, 1, 'consulta_catalogo', 'catalogo', 'Consultando catálogo - Total libros: 26, Página 1/2', '2026-02-01 15:35:37'),
(95, 1, 'acceso', 'historial', 'Accedió al historial de préstamos', '2026-02-01 15:35:39'),
(96, 1, 'consulta_historial', 'historial', 'Consultando historial con 0 filtros, 0 parámetros', '2026-02-01 15:35:39'),
(97, 1, 'total_obtenido', 'historial', 'Total de registros en historial: 0', '2026-02-01 15:35:39'),
(98, 1, 'consulta_exitosa', 'historial', 'Historial consultado exitosamente - 0 registros encontrados', '2026-02-01 15:35:39'),
(99, 1, 'acceso', 'catalogo', 'Accedió al catálogo de libros', '2026-02-01 15:35:40'),
(100, 1, 'consulta_catalogo', 'catalogo', 'Consultando catálogo - Total libros: 26, Página 1/2', '2026-02-01 15:35:40'),
(101, 1, 'acceso', 'catalogo', 'Accedió al catálogo de libros', '2026-02-01 15:35:45'),
(102, 1, 'consulta_catalogo', 'catalogo', 'Consultando catálogo - Total libros: 26, Página 2/2', '2026-02-01 15:35:45'),
(103, 1, 'acceso', 'catalogo', 'Accedió al catálogo de libros', '2026-02-01 15:42:09'),
(104, 1, 'consulta_catalogo', 'catalogo', 'Consultando catálogo - Total libros: 26, Página 2/2', '2026-02-01 15:42:09'),
(105, 1, 'acceso', 'catalogo', 'Accedió al catálogo de libros', '2026-02-01 15:42:15'),
(106, 1, 'consulta_catalogo', 'catalogo', 'Consultando catálogo - Total libros: 26, Página 1/2', '2026-02-01 15:42:15'),
(107, 1, 'acceso', 'catalogo', 'Accedió al catálogo de libros', '2026-02-01 15:42:20'),
(108, 1, 'consulta_catalogo', 'catalogo', 'Consultando catálogo - Total libros: 26, Página 2/2', '2026-02-01 15:42:20'),
(109, 1, 'acceso', 'catalogo', 'Accedió al catálogo de libros', '2026-02-01 15:44:03'),
(110, 1, 'consulta_catalogo', 'catalogo', 'Consultando catálogo - Total libros: 26, Página 2/2', '2026-02-01 15:44:03'),
(111, 1, 'acceso', 'catalogo', 'Accedió al catálogo de libros', '2026-02-01 15:47:10'),
(112, 1, 'consulta_catalogo', 'catalogo', 'Consultando catálogo - Total libros: 27, Página 1/2', '2026-02-01 15:47:10'),
(113, 1, 'acceso', 'catalogo', 'Accedió al catálogo de libros', '2026-02-01 15:47:12'),
(114, 1, 'consulta_catalogo', 'catalogo', 'Consultando catálogo - Total libros: 27, Página 1/2', '2026-02-01 15:47:12'),
(115, 1, 'acceso', 'catalogo', 'Accedió al catálogo de libros', '2026-02-01 15:47:14'),
(116, 1, 'consulta_catalogo', 'catalogo', 'Consultando catálogo - Total libros: 27, Página 2/2', '2026-02-01 15:47:14'),
(117, 1, 'acceso', 'catalogo', 'Accedió al catálogo de libros', '2026-02-01 15:53:37'),
(118, 1, 'consulta_catalogo', 'catalogo', 'Consultando catálogo - Total libros: 27, Página 1/2', '2026-02-01 15:53:37'),
(119, 1, 'acceso', 'catalogo', 'Accedió al catálogo de libros', '2026-02-01 16:41:15'),
(120, 1, 'consulta_catalogo', 'catalogo', 'Consultando catálogo - Total libros: 50, Página 1/3', '2026-02-01 16:41:15'),
(121, 1, 'acceso', 'catalogo', 'Accedió al catálogo de libros', '2026-02-01 16:41:27'),
(122, 1, 'consulta_catalogo', 'catalogo', 'Consultando catálogo - Total libros: 1, Página 1/1', '2026-02-01 16:41:27'),
(123, 1, 'acceso_edicion', 'catalogo', 'Accedió a editar libro', '2026-02-01 16:44:17'),
(124, 1, 'consulta_libro', 'catalogo', 'Consultando datos del libro ID: 36', '2026-02-01 16:44:17'),
(125, 1, 'libro_encontrado', 'catalogo', 'Editando libro - ID: 36, Título: \'Il Nuovo Testamento Annotato (Volume IV)\', Código: LIB-CO-034', '2026-02-01 16:44:17'),
(126, 1, 'estadisticas_prestamos', 'catalogo', 'Estadísticas préstamos libro ID: 36 - Total: 0, Activos: ', '2026-02-01 16:44:17'),
(127, 1, 'acceso_edicion', 'catalogo', 'Accedió a editar libro', '2026-02-01 16:45:49'),
(128, 1, 'consulta_libro', 'catalogo', 'Consultando datos del libro ID: 36', '2026-02-01 16:45:49'),
(129, 1, 'libro_encontrado', 'catalogo', 'Editando libro - ID: 36, Título: \'Il Nuovo Testamento Annotato (Volume IV)\', Código: LIB-CO-034', '2026-02-01 16:45:49'),
(130, 1, 'inicio_actualizacion', 'catalogo', 'Iniciando actualización del libro ID: 36', '2026-02-01 16:45:49'),
(131, 1, 'inicio_transaccion', 'catalogo', 'Iniciando transacción para libro ID: 36', '2026-02-01 16:45:49'),
(132, 1, 'libro_actualizado_bd', 'catalogo', 'Libro actualizado en BD - ID: 36', '2026-02-01 16:45:49'),
(133, 1, 'categorias_eliminadas', 'catalogo', 'Categorías anteriores eliminadas para libro ID: 36', '2026-02-01 16:45:49'),
(134, 1, 'categorias_asignadas', 'catalogo', '1 categorías asignadas a libro ID: 36', '2026-02-01 16:45:49'),
(135, 1, 'transaccion_exitosa', 'catalogo', 'Transacción completada exitosamente para libro ID: 36', '2026-02-01 16:45:49'),
(136, 1, 'cambios_detallados', 'catalogo', 'Cambios en libro ID: 36 - título: \'Il Nuovo Testamento Annotato (Volume IV)\' → \'Il Nuovo Testamento Annotato (Volume IV) I,II Timoteo-Tito-Filemone-Ebrei-Giacomo-I,II Pietro-I,II, III Giovanni-Giuda-Apocalisse\'', '2026-02-01 16:45:49'),
(137, 1, 'estadisticas_prestamos', 'catalogo', 'Estadísticas préstamos libro ID: 36 - Total: 0, Activos: ', '2026-02-01 16:45:49'),
(138, 1, 'acceso', 'catalogo', 'Accedió al catálogo de libros', '2026-02-01 16:45:53'),
(139, 1, 'consulta_catalogo', 'catalogo', 'Consultando catálogo - Total libros: 50, Página 1/3', '2026-02-01 16:45:53'),
(140, 1, 'acceso', 'catalogo', 'Accedió al catálogo de libros', '2026-02-01 16:45:55'),
(141, 1, 'consulta_catalogo', 'catalogo', 'Consultando catálogo - Total libros: 50, Página 3/3', '2026-02-01 16:45:55'),
(142, 1, 'acceso', 'catalogo', 'Accedió al catálogo de libros', '2026-02-01 16:46:07'),
(143, 1, 'consulta_catalogo', 'catalogo', 'Consultando catálogo - Total libros: 50, Página 2/3', '2026-02-01 16:46:07'),
(144, 1, 'acceso', 'catalogo', 'Accedió al catálogo de libros', '2026-02-01 17:33:46'),
(145, 1, 'consulta_catalogo', 'catalogo', 'Consultando catálogo - Total libros: 68, Página 1/4', '2026-02-01 17:33:46'),
(146, 1, 'acceso', 'catalogo', 'Accedió al catálogo de libros', '2026-02-01 17:33:49'),
(147, 1, 'consulta_catalogo', 'catalogo', 'Consultando catálogo - Total libros: 68, Página 2/4', '2026-02-01 17:33:49'),
(148, 1, 'acceso', 'catalogo', 'Accedió al catálogo de libros', '2026-02-01 17:33:50'),
(149, 1, 'consulta_catalogo', 'catalogo', 'Consultando catálogo - Total libros: 68, Página 4/4', '2026-02-01 17:33:50'),
(150, 1, 'acceso', 'catalogo', 'Accedió al catálogo de libros', '2026-02-01 17:34:10'),
(151, 1, 'consulta_catalogo', 'catalogo', 'Consultando catálogo - Total libros: 68, Página 1/4', '2026-02-01 17:34:10'),
(152, 1, 'acceso', 'catalogo', 'Accedió al catálogo de libros', '2026-02-01 17:34:21'),
(153, 1, 'consulta_catalogo', 'catalogo', 'Consultando catálogo - Total libros: 7, Página 1/1', '2026-02-01 17:34:21'),
(154, 1, 'acceso_edicion', 'catalogo', 'Accedió a editar libro', '2026-02-01 17:34:31'),
(155, 1, 'consulta_libro', 'catalogo', 'Consultando datos del libro ID: 71', '2026-02-01 17:34:31'),
(156, 1, 'libro_encontrado', 'catalogo', 'Editando libro - ID: 71, Título: \'Storia del popolo Giudaico al tempo di Gesù Cristo\', Código: LIB-CH-007', '2026-02-01 17:34:31'),
(157, 1, 'estadisticas_prestamos', 'catalogo', 'Estadísticas préstamos libro ID: 71 - Total: 0, Activos: ', '2026-02-01 17:34:31'),
(158, 1, 'acceso_edicion', 'catalogo', 'Accedió a editar libro', '2026-02-01 17:34:45'),
(159, 1, 'consulta_libro', 'catalogo', 'Consultando datos del libro ID: 71', '2026-02-01 17:34:45'),
(160, 1, 'libro_encontrado', 'catalogo', 'Editando libro - ID: 71, Título: \'Storia del popolo Giudaico al tempo di Gesù Cristo\', Código: LIB-CH-007', '2026-02-01 17:34:45'),
(161, 1, 'inicio_actualizacion', 'catalogo', 'Iniciando actualización del libro ID: 71', '2026-02-01 17:34:45'),
(162, 1, 'inicio_transaccion', 'catalogo', 'Iniciando transacción para libro ID: 71', '2026-02-01 17:34:45'),
(163, 1, 'libro_actualizado_bd', 'catalogo', 'Libro actualizado en BD - ID: 71', '2026-02-01 17:34:45'),
(164, 1, 'categorias_eliminadas', 'catalogo', 'Categorías anteriores eliminadas para libro ID: 71', '2026-02-01 17:34:45'),
(165, 1, 'categorias_asignadas', 'catalogo', '1 categorías asignadas a libro ID: 71', '2026-02-01 17:34:45'),
(166, 1, 'transaccion_exitosa', 'catalogo', 'Transacción completada exitosamente para libro ID: 71', '2026-02-01 17:34:45'),
(167, 1, 'cambios_detallados', 'catalogo', 'Cambios en libro ID: 71 - título: \'Storia del popolo Giudaico al tempo di Gesù Cristo\' → \'Storia del popolo Giudaico al tempo di Gesù Cristo (Volume I)\'', '2026-02-01 17:34:45'),
(168, 1, 'estadisticas_prestamos', 'catalogo', 'Estadísticas préstamos libro ID: 71 - Total: 0, Activos: ', '2026-02-01 17:34:45'),
(169, 1, 'acceso', 'catalogo', 'Accedió al catálogo de libros', '2026-02-01 17:34:47'),
(170, 1, 'consulta_catalogo', 'catalogo', 'Consultando catálogo - Total libros: 68, Página 1/4', '2026-02-01 17:34:47'),
(171, 1, 'acceso', 'catalogo', 'Accedió al catálogo de libros', '2026-02-01 17:36:01'),
(172, 1, 'consulta_catalogo', 'catalogo', 'Consultando catálogo - Total libros: 69, Página 1/4', '2026-02-01 17:36:01'),
(173, 1, 'acceso', 'catalogo', 'Accedió al catálogo de libros', '2026-02-01 17:39:42'),
(174, 1, 'consulta_catalogo', 'catalogo', 'Consultando catálogo - Total libros: 71, Página 1/4', '2026-02-01 17:39:42'),
(175, 1, 'acceso', 'catalogo', 'Accedió al catálogo de libros', '2026-02-01 17:40:07'),
(176, 1, 'consulta_catalogo', 'catalogo', 'Consultando catálogo - Total libros: 71, Página 1/4', '2026-02-01 17:40:07'),
(177, 1, 'acceso', 'catalogo', 'Accedió al catálogo de libros', '2026-02-01 17:40:11'),
(178, 1, 'consulta_catalogo', 'catalogo', 'Consultando catálogo - Total libros: 71, Página 2/4', '2026-02-01 17:40:11'),
(179, 1, 'acceso', 'catalogo', 'Accedió al catálogo de libros', '2026-02-01 17:40:15'),
(180, 1, 'consulta_catalogo', 'catalogo', 'Consultando catálogo - Total libros: 71, Página 3/4', '2026-02-01 17:40:15'),
(181, 1, 'acceso', 'catalogo', 'Accedió al catálogo de libros', '2026-02-01 17:40:17'),
(182, 1, 'consulta_catalogo', 'catalogo', 'Consultando catálogo - Total libros: 71, Página 4/4', '2026-02-01 17:40:17'),
(183, 1, 'acceso', 'catalogo', 'Accedió al catálogo de libros', '2026-02-01 17:40:20'),
(184, 1, 'consulta_catalogo', 'catalogo', 'Consultando catálogo - Total libros: 71, Página 1/4', '2026-02-01 17:40:20'),
(185, 1, 'inicio_exportacion', 'catalogo', 'Iniciando exportación de catálogo en formato profesional', '2026-02-01 17:40:23'),
(186, 1, 'exportacion_exitosa', 'catalogo', 'Exportación profesional completada - 71 libros exportados', '2026-02-01 17:40:23'),
(187, 1, 'acceso', 'catalogo', 'Accedió al catálogo de libros', '2026-02-01 17:45:48'),
(188, 1, 'consulta_catalogo', 'catalogo', 'Consultando catálogo - Total libros: 71, Página 1/4', '2026-02-01 17:45:48'),
(189, 1, 'acceso', 'etiquetas', 'Accedió al generador de etiquetas', '2026-02-01 17:45:49'),
(190, 1, 'generacion_etiquetas_ajax', 'etiquetas', 'Generadas 71 etiquetas para 71 libros via AJAX', '2026-02-01 17:46:20'),
(191, 1, 'acceso', 'catalogo', 'Accedió al catálogo de libros', '2026-02-01 17:59:55'),
(192, 1, 'consulta_catalogo', 'catalogo', 'Consultando catálogo - Total libros: 154, Página 1/8', '2026-02-01 17:59:55'),
(193, 1, 'acceso', 'catalogo', 'Accedió al catálogo de libros', '2026-02-01 18:00:02'),
(194, 1, 'consulta_catalogo', 'catalogo', 'Consultando catálogo - Total libros: 154, Página 3/8', '2026-02-01 18:00:02'),
(195, 1, 'acceso', 'catalogo', 'Accedió al catálogo de libros', '2026-02-01 18:00:05'),
(196, 1, 'consulta_catalogo', 'catalogo', 'Consultando catálogo - Total libros: 154, Página 4/8', '2026-02-01 18:00:05'),
(197, 1, 'acceso', 'catalogo', 'Accedió al catálogo de libros', '2026-02-01 18:00:13'),
(198, 1, 'consulta_catalogo', 'catalogo', 'Consultando catálogo - Total libros: 154, Página 5/8', '2026-02-01 18:00:13'),
(199, 1, 'acceso', 'catalogo', 'Accedió al catálogo de libros', '2026-02-01 18:00:23'),
(200, 1, 'consulta_catalogo', 'catalogo', 'Consultando catálogo - Total libros: 154, Página 6/8', '2026-02-01 18:00:23'),
(201, 1, 'acceso', 'catalogo', 'Accedió al catálogo de libros', '2026-02-01 18:00:25'),
(202, 1, 'consulta_catalogo', 'catalogo', 'Consultando catálogo - Total libros: 154, Página 7/8', '2026-02-01 18:00:25'),
(203, 1, 'acceso', 'catalogo', 'Accedió al catálogo de libros', '2026-02-01 18:00:26'),
(204, 1, 'consulta_catalogo', 'catalogo', 'Consultando catálogo - Total libros: 154, Página 8/8', '2026-02-01 18:00:26'),
(205, 1, 'acceso', 'catalogo', 'Accedió al catálogo de libros', '2026-02-01 18:01:21'),
(206, 1, 'consulta_catalogo', 'catalogo', 'Consultando catálogo - Total libros: 154, Página 1/8', '2026-02-01 18:01:21'),
(207, 1, 'acceso', 'catalogo', 'Accedió al catálogo de libros', '2026-02-01 18:01:24'),
(208, 1, 'consulta_catalogo', 'catalogo', 'Consultando catálogo - Total libros: 154, Página 2/8', '2026-02-01 18:01:24'),
(209, 1, 'acceso', 'catalogo', 'Accedió al catálogo de libros', '2026-02-01 18:01:26'),
(210, 1, 'consulta_catalogo', 'catalogo', 'Consultando catálogo - Total libros: 154, Página 3/8', '2026-02-01 18:01:26'),
(211, 1, 'acceso', 'catalogo', 'Accedió al catálogo de libros', '2026-02-01 18:01:27'),
(212, 1, 'consulta_catalogo', 'catalogo', 'Consultando catálogo - Total libros: 154, Página 4/8', '2026-02-01 18:01:27'),
(213, 1, 'acceso', 'catalogo', 'Accedió al catálogo de libros', '2026-02-01 18:01:30'),
(214, 1, 'consulta_catalogo', 'catalogo', 'Consultando catálogo - Total libros: 154, Página 3/8', '2026-02-01 18:01:30'),
(215, 1, 'acceso', 'catalogo', 'Accedió al catálogo de libros', '2026-02-01 18:01:32'),
(216, 1, 'consulta_catalogo', 'catalogo', 'Consultando catálogo - Total libros: 154, Página 1/8', '2026-02-01 18:01:32');

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
(1, 'admin', '$2y$10$w6cNC5JuJgxPR5iaEyGD4ujDj2sJRP0GVk3sj6TeBms2OYuSZZIra', 'Encargado de Biblioteca', 1, '2026-01-26 23:00:46', '2026-02-01 13:59:49');

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=158;

--
-- AUTO_INCREMENT per la tabella `logs_actividad`
--
ALTER TABLE `logs_actividad`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=217;

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
