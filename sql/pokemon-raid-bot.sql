
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attendance` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) DEFAULT NULL,
  `raid_id` int(10) unsigned DEFAULT NULL,
  `attend_time` datetime DEFAULT NULL,
  `extra_mystic` tinyint(1) unsigned DEFAULT '0',
  `extra_valor` tinyint(1) unsigned DEFAULT '0',
  `extra_instinct` tinyint(1) unsigned DEFAULT '0',
  `arrived` tinyint(1) unsigned DEFAULT '0',
  `raid_done` tinyint(1) unsigned DEFAULT '0',
  `cancel` tinyint(1) unsigned DEFAULT '0',
  `late` tinyint(1) unsigned DEFAULT '0',
  `remote` tinyint(1) unsigned DEFAULT '0',
  `invite` tinyint(1) unsigned DEFAULT '0',
  `pokemon` varchar(20) DEFAULT '0',
  `alarm` tinyint(1) unsigned DEFAULT '0',
  `want_invite` tinyint(1) unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `raid_id` (`raid_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cleanup` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `raid_id` int(10) unsigned NOT NULL,
  `chat_id` bigint(20) NOT NULL,
  `message_id` bigint(20) unsigned NOT NULL,
  `cleaned` int(10) unsigned DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gyms` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lat` decimal(19,16) DEFAULT NULL,
  `lon` decimal(19,16) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `gym_name` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
  `ex_gym` tinyint(1) unsigned DEFAULT '0',
  `show_gym` tinyint(1) unsigned DEFAULT '0',
  `gym_note` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
  `gym_id` varchar(40) DEFAULT NULL,
  `img_url` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `index_gym_id` (`gym_id`),
  KEY `gym_lat_lon` (`lat`, `lon`)
) ENGINE=InnoDB AUTO_INCREMENT=100 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `overview` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `chat_id` bigint(20) NOT NULL,
  `message_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pokemon` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pokedex_id` int(10) unsigned NOT NULL,
  `pokemon_name` varchar(12) DEFAULT NULL,
  `pokemon_form_name` varchar(45) DEFAULT NULL,
  `pokemon_form_id` int(4) DEFAULT NULL,
  `raid_level` enum('0','1','2','3','4','5', '6','X') DEFAULT '0',
  `min_cp` int(10) unsigned NOT NULL,
  `max_cp` int(10) unsigned NOT NULL,
  `min_weather_cp` int(10) unsigned NOT NULL,
  `max_weather_cp` int(10) unsigned NOT NULL,
  `weather` int(10) unsigned NOT NULL,
  `shiny` tinyint(1) unsigned DEFAULT '0',
  `asset_suffix` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_pokemon_pokedex_id_pokemon_form_id` (`pokedex_id`,`pokemon_form_id`)
) ENGINE=InnoDB AUTO_INCREMENT=100 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `raids` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) DEFAULT NULL,
  `pokemon` int(4) DEFAULT NULL,
  `pokemon_form` int(4) NOT NULL DEFAULT 0,
  `first_seen` datetime DEFAULT NULL,
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `gym_team` enum('mystic','valor','instinct') DEFAULT NULL,
  `gym_id` int(10) unsigned NOT NULL,
  `move1` varchar(255) DEFAULT NULL,
  `move2` varchar(255) DEFAULT NULL,
  `gender` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `end_time` (`end_time`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=100 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `trainerinfo` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `chat_id` bigint(20) NOT NULL,
  `message_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) DEFAULT NULL,
  `nick` varchar(100) CHARACTER SET utf8mb4 DEFAULT NULL,
  `name` varchar(200) CHARACTER SET utf8mb4 DEFAULT NULL,
  `trainername` varchar(200) CHARACTER SET utf8mb4 DEFAULT NULL,
  `team` enum('mystic','valor','instinct') DEFAULT NULL,
  `level` int(10) unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `i_userid` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=100 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

