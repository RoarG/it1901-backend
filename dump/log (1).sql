-- phpMyAdmin SQL Dump
-- version 3.4.10.1deb1
-- http://www.phpmyadmin.net
--
-- Vert: localhost
-- Generert den: 10. Sep, 2013 15:26 PM
-- Tjenerversjon: 5.5.32
-- PHP-Versjon: 5.3.10-1ubuntu3.7

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `prosjekt1`
--

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `log`
--

CREATE TABLE IF NOT EXISTS `log` (
  `id` bigint(22) NOT NULL AUTO_INCREMENT,
  `user` bigint(22) DEFAULT NULL,
  `method` varchar(20) DEFAULT NULL,
  `call_url` varchar(30) DEFAULT NULL,
  `url` text,
  `time` datetime DEFAULT NULL,
  `user_agent` varchar(225) DEFAULT NULL,
  `get` text,
  `post` text,
  `response` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

--
-- Dataark for tabell `log`
--

INSERT INTO `log` (`id`, `user`, `method`, `call_url`, `url`, `time`, `user_agent`, `get`, `post`, `response`) VALUES
(1, 1, 'test123', 'test123', 'test123', '2013-09-01 20:48:45', 'test123', 'test123', 'test123', 'test123');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
