-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server Version:               10.1.21-MariaDB - MariaDB Server
-- Server Betriebssystem:        Linux
-- HeidiSQL Version:             9.4.0.5151
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

-- Exportiere Struktur von Tabelle emcwallet.wallet_address
CREATE TABLE IF NOT EXISTS `wallet_address` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(11) unsigned DEFAULT NULL,
  `address` varchar(40) DEFAULT NULL,
  `label` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1399 DEFAULT CHARSET=latin1;

-- Daten Export vom Benutzer nicht ausgewählt
-- Exportiere Struktur von Tabelle emcwallet.wallet_addressbook
CREATE TABLE IF NOT EXISTS `wallet_addressbook` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `name` varchar(50) DEFAULT NULL,
  `address` varchar(50) DEFAULT NULL,
  `valid` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=88 DEFAULT CHARSET=latin1;

-- Daten Export vom Benutzer nicht ausgewählt
-- Exportiere Struktur von Tabelle emcwallet.wallet_balance
CREATE TABLE IF NOT EXISTS `wallet_balance` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(10) unsigned DEFAULT NULL,
  `balance` double unsigned DEFAULT NULL,
  `time` int(10) unsigned DEFAULT NULL,
  `coinsec` bigint(20) unsigned DEFAULT NULL,
  `coinavg` float unsigned DEFAULT NULL,
  `stake` float unsigned DEFAULT NULL,
  `interest` float unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=284757 DEFAULT CHARSET=latin1 ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;

-- Daten Export vom Benutzer nicht ausgewählt
-- Exportiere Struktur von Tabelle emcwallet.wallet_log
CREATE TABLE IF NOT EXISTS `wallet_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `time` int(10) unsigned DEFAULT NULL,
  `category` varchar(10) DEFAULT NULL,
  `log` varchar(500) DEFAULT NULL,
  `userid` int(10) unsigned DEFAULT NULL,
  `txid` int(10) unsigned DEFAULT NULL,
  `addressid` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

-- Daten Export vom Benutzer nicht ausgewählt
-- Exportiere Struktur von Tabelle emcwallet.wallet_nvs
CREATE TABLE IF NOT EXISTS `wallet_nvs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(10) unsigned DEFAULT NULL,
  `name` varchar(1000) DEFAULT NULL,
  `registered_at` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=582 DEFAULT CHARSET=latin1;

-- Daten Export vom Benutzer nicht ausgewählt
-- Exportiere Struktur von Tabelle emcwallet.wallet_send_queue
CREATE TABLE IF NOT EXISTS `wallet_send_queue` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(11) DEFAULT NULL,
  `time` int(11) DEFAULT NULL,
  `confirmations` tinyint(4) DEFAULT NULL,
  `tx_details` varchar(500) DEFAULT NULL,
  `address` varchar(40) DEFAULT NULL,
  `amount` double DEFAULT NULL,
  `service_fee` double DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=latin1;

-- Daten Export vom Benutzer nicht ausgewählt
-- Exportiere Struktur von Tabelle emcwallet.wallet_stake
CREATE TABLE IF NOT EXISTS `wallet_stake` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `time` int(10) unsigned DEFAULT NULL,
  `txid` varchar(512) DEFAULT NULL,
  `address` varchar(40) DEFAULT NULL,
  `amount` double DEFAULT NULL,
  `service_fee` double DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `txid` (`txid`)
) ENGINE=InnoDB AUTO_INCREMENT=616 DEFAULT CHARSET=latin1;

-- Daten Export vom Benutzer nicht ausgewählt
-- Exportiere Struktur von Tabelle emcwallet.wallet_transaction
CREATE TABLE IF NOT EXISTS `wallet_transaction` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(10) unsigned DEFAULT NULL,
  `addressid` int(10) unsigned DEFAULT NULL,
  `time` int(10) unsigned DEFAULT NULL,
  `confirmations` tinyint(4) DEFAULT NULL,
  `txid` varchar(512) DEFAULT NULL,
  `tx_details` varchar(1000) DEFAULT NULL,
  `address` varchar(40) DEFAULT NULL,
  `category` varchar(10) DEFAULT NULL,
  `amount` double DEFAULT NULL,
  `fee` double DEFAULT NULL,
  `service_fee` double DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `txid` (`txid`)
) ENGINE=InnoDB AUTO_INCREMENT=16946 DEFAULT CHARSET=latin1;

-- Daten Export vom Benutzer nicht ausgewählt
-- Exportiere Struktur von Tabelle emcwallet.wallet_user
CREATE TABLE IF NOT EXISTS `wallet_user` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `emcssl` varchar(25) DEFAULT NULL,
  `pw` varchar(150) DEFAULT NULL,
  `emcsslauth` tinyint(4) DEFAULT NULL,
  `sessionid` varchar(50) DEFAULT NULL,
  `mailcheck` varchar(32) DEFAULT NULL,
  `pwrequest` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1211 DEFAULT CHARSET=latin1;

-- Daten Export vom Benutzer nicht ausgewählt
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
