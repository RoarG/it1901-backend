-- phpMyAdmin SQL Dump
-- version 3.4.10.1deb1
-- http://www.phpmyadmin.net
--
-- Vert: localhost
-- Generert den: 10. Sep, 2013 19:32 PM
-- Tjenerversjon: 5.5.32
-- PHP-Versjon: 5.3.10-1ubuntu3.8

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

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `notification`
--

CREATE TABLE IF NOT EXISTS `notification` (
  `id` bigint(22) NOT NULL AUTO_INCREMENT,
  `system` int(4) NOT NULL,
  `text` text,
  `sheep` int(7) NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `sheep`
--

CREATE TABLE IF NOT EXISTS `sheep` (
  `id` int(7) NOT NULL AUTO_INCREMENT,
  `identification` int(12) DEFAULT NULL,
  `name` varchar(150) DEFAULT NULL,
  `birthday` date NOT NULL,
  `alive` tinyint(1) NOT NULL DEFAULT '1',
  `last_updated` datetime DEFAULT NULL,
  `lat` decimal(18,12) DEFAULT NULL,
  `lng` decimal(18,12) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `system`
--

CREATE TABLE IF NOT EXISTS `system` (
  `id` int(4) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `owner` int(4) DEFAULT NULL,
  `active` tinyint(1) DEFAULT '1',
  `sheep_token` varchar(225) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `user`
--

CREATE TABLE IF NOT EXISTS `user` (
  `id` int(4) NOT NULL AUTO_INCREMENT,
  `mail` varchar(150) NOT NULL,
  `pswd` varchar(225) NOT NULL,
  `token` varchar(225) DEFAULT NULL,
  `name` varchar(150) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `contact_mail` tinyint(1) NOT NULL DEFAULT '1',
  `contact_sms` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
