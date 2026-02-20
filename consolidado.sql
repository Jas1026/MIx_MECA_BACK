-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 02-05-2024 a las 18:43:54
-- Versión del servidor: 8.0.31
-- Versión de PHP: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `mecapos`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `consolidado`
--

DROP TABLE IF EXISTS `consolidado`;
CREATE TABLE IF NOT EXISTS `consolidado` (
  `id` int NOT NULL AUTO_INCREMENT,
  `mesero` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `mesa` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `categoria` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `producto` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `cantidad` int NOT NULL,
  `precio` float NOT NULL,
  `notas` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `acomp` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `estado` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `id_product_ticket` int NOT NULL,
  `id_ticket` int NOT NULL,
  `creado` timestamp NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
