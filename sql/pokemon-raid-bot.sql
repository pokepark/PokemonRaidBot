CREATE TABLE `attendance` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) DEFAULT NULL,
  `raid_id` int(10) unsigned DEFAULT NULL,
  `attend_time` datetime DEFAULT NULL,
  `extra_in_person` tinyint(1) unsigned DEFAULT '0',
  `extra_alien` tinyint(1) unsigned DEFAULT '0',
  `arrived` tinyint(1) unsigned DEFAULT '0',
  `raid_done` tinyint(1) unsigned DEFAULT '0',
  `cancel` tinyint(1) unsigned DEFAULT '0',
  `late` tinyint(1) unsigned DEFAULT '0',
  `remote` tinyint(1) unsigned DEFAULT '0',
  `invite` tinyint(1) unsigned DEFAULT '0',
  `pokemon` varchar(20) DEFAULT '0',
  `alarm` tinyint(1) unsigned DEFAULT '0',
  `want_invite` tinyint(1) unsigned DEFAULT '0',
  `can_invite` TINYINT UNSIGNED NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `raid_id` (`raid_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `cleanup` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `raid_id` int(10) unsigned NOT NULL,
  `chat_id` bigint(20) NOT NULL,
  `message_id` bigint(20) unsigned NOT NULL,
  `type` VARCHAR(20) NULL,
  `date_of_posting` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `events` (
  `id` int(3) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL,
  `description` varchar(200) NOT NULL,
  `vote_key_mode` int(3) NOT NULL DEFAULT 0,
  `time_slots` int(3) DEFAULT NULL,
  `raid_duration` int(3) unsigned NOT NULL DEFAULT 0,
  `hide_raid_picture` tinyint(1) DEFAULT 0,
  `poll_template` VARCHAR(200) NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `gyms` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lat` decimal(19,16) DEFAULT NULL,
  `lon` decimal(19,16) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `gym_name` varchar(255) DEFAULT NULL,
  `ex_gym` tinyint(1) unsigned DEFAULT '0',
  `show_gym` tinyint(1) unsigned DEFAULT '0',
  `gym_note` varchar(255) DEFAULT NULL,
  `gym_id` varchar(40) DEFAULT NULL,
  `img_url` varchar(255) DEFAULT NULL,
  `temporary_gym` tinyint(1) unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `index_gym_id` (`gym_id`),
  KEY `gym_lat_lon` (`lat`, `lon`)
) ENGINE=InnoDB AUTO_INCREMENT=100 DEFAULT CHARSET=utf8mb4;
CREATE TABLE `overview` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `chat_id` bigint(20) NOT NULL,
  `message_id` bigint(20) unsigned NOT NULL,
  `chat_title` varchar(128) DEFAULT NULL,
  `chat_username` varchar(32) DEFAULT NULL,
  `updated` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `pokemon` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pokedex_id` int(10) unsigned NOT NULL,
  `pokemon_name` varchar(12) DEFAULT NULL,
  `pokemon_form_name` varchar(45) DEFAULT NULL,
  `pokemon_form_id` int(4) DEFAULT NULL,
  `min_cp` int(10) unsigned NOT NULL DEFAULT 0,
  `max_cp` int(10) unsigned NOT NULL DEFAULT 0,
  `min_weather_cp` int(10) unsigned NOT NULL DEFAULT 0,
  `max_weather_cp` int(10) unsigned NOT NULL DEFAULT 0,
  `weather` int(10) unsigned NOT NULL DEFAULT 0,
  `type` varchar(10) DEFAULT '',
  `type2` varchar(10) DEFAULT '',
  `shiny` tinyint(1) unsigned DEFAULT 0,
  `asset_suffix` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_pokemon_pokedex_id_pokemon_form_id` (`pokedex_id`,`pokemon_form_id`)
) ENGINE=InnoDB AUTO_INCREMENT=100 DEFAULT CHARSET=utf8mb4;
CREATE TABLE `raid_bosses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pokedex_id` int(10) DEFAULT NULL,
  `pokemon_form_id` int(4) DEFAULT NULL,
  `date_start` datetime NOT NULL DEFAULT '1970-01-01 00:00:01',
  `date_end` datetime NOT NULL DEFAULT '2038-01-19 03:14:07',
  `raid_level` enum('1','2','3','4','5','6','X') DEFAULT NULL,
  `scheduled` TINYINT(1) NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `raids` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) DEFAULT NULL,
  `pokemon` int(4) DEFAULT NULL,
  `pokemon_form` int(4) NOT NULL DEFAULT 0,
  `spawn` datetime DEFAULT NULL,
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `gym_team` enum('mystic','valor','instinct') DEFAULT NULL,
  `gym_id` int(10) unsigned NOT NULL,
  `level` enum('1','2','3','4','5','6','X') DEFAULT NULL,
  `move1` varchar(255) DEFAULT NULL,
  `move2` varchar(255) DEFAULT NULL,
  `gender` varchar(255) DEFAULT NULL,
  `event` int(3) unsigned DEFAULT NULL,
  `event_note` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `end_time` (`end_time`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=100 DEFAULT CHARSET=utf8mb4;
CREATE TABLE `trainerinfo` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `chat_id` bigint(20) NOT NULL,
  `message_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `user_input` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `handler` varchar(45) DEFAULT NULL,
  `modifiers` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) DEFAULT NULL,
  `nick` varchar(100) DEFAULT NULL,
  `name` varchar(200) DEFAULT NULL,
  `trainername` varchar(200) DEFAULT NULL,
  `display_name` int(1) NOT NULL DEFAULT 0,
  `trainercode` varchar(12) DEFAULT NULL,
  `team` enum('mystic','valor','instinct') DEFAULT NULL,
  `level` int(10) unsigned DEFAULT '0',
  `lang` VARCHAR(10) NULL,
  `lang_manual` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `tutorial` TINYINT(1) NOT NULL DEFAULT 0,
  `auto_alarm` TINYINT(1) UNSIGNED NULL DEFAULT 0,
   PRIMARY KEY (`id`),
  UNIQUE KEY `i_userid` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=100 DEFAULT CHARSET=utf8mb4;
