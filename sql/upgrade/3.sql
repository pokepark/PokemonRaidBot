ALTER TABLE `gyms` ADD KEY IF NOT EXISTS `gym_lat_lon` (`lat`, `lon`);

CREATE UNIQUE INDEX IF NOT EXISTS `idx_pokemon_pokedex_id_pokemon_form_id` ON `pokemon` (pokedex_id, pokemon_form_id);

ALTER TABLE `raids`
CHANGE COLUMN IF EXISTS `first_seen` `spawn` DATETIME NULL DEFAULT NULL,
MODIFY `pokemon` int(4) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `pokemon_form` int(4) unsigned NOT NULL DEFAULT 0,
ADD COLUMN IF NOT EXISTS `level` enum('1','2','3','4','5','6','X') DEFAULT NULL AFTER `gym_id`,
ADD COLUMN IF NOT EXISTS `event` int(3) unsigned DEFAULT NULL AFTER `gender`,
ADD COLUMN IF NOT EXISTS `event_note` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL AFTER `event`;

CREATE TABLE IF NOT EXISTS `raid_bosses` (`id` int(11) NOT NULL AUTO_INCREMENT,`pokedex_id` int(10) DEFAULT NULL,`pokemon_form_id` int(4) DEFAULT NULL,`date_start` datetime NOT NULL DEFAULT '1970-01-01 00:00:01',`date_end` datetime NOT NULL DEFAULT '2038-01-19 03:14:07',`raid_level` enum('1','2','3','4','5','6','X') DEFAULT NULL,PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO `raid_bosses` (`pokedex_id`,`pokemon_form_id`,`raid_level`) SELECT `pokedex_id`,`pokemon_form_id`,`raid_level` FROM pokemon WHERE raid_level != '0';

ALTER TABLE `users`
ADD COLUMN IF NOT EXISTS `trainername` varchar(200) CHARACTER SET utf8mb4 DEFAULT NULL AFTER `name`,
ADD COLUMN IF NOT EXISTS `display_name` INT(1) NOT NULL DEFAULT 0 AFTER `trainername`,
ADD COLUMN IF NOT EXISTS `trainercode` varchar(12) CHARACTER SET utf8mb4 DEFAULT NULL AFTER `display_name`,
ADD COLUMN IF NOT EXISTS `lang` VARCHAR(10) NULL AFTER `level`,
ADD COLUMN IF NOT EXISTS `lang_manual` TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER `lang`,
ADD COLUMN IF NOT EXISTS `tutorial` TINYINT(1) NOT NULL DEFAULT 0 AFTER `lang_manual`;

CREATE TABLE IF NOT EXISTS `user_input` (`id` int(11) NOT NULL AUTO_INCREMENT,`user_id` int(11) DEFAULT NULL,`handler` varchar(45) DEFAULT NULL,`modifiers` text DEFAULT NULL,PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `events` (`id` int(3) unsigned NOT NULL AUTO_INCREMENT,`name` varchar(45) CHARACTER SET utf8mb4 NOT NULL,`description` varchar(200) CHARACTER SET utf8mb4 NOT NULL,`vote_key_mode` int(3) NOT NULL DEFAULT 0,`time_slots` int(3) DEFAULT NULL,`raid_duration` int(3) unsigned NOT NULL DEFAULT 0,`hide_raid_picture` tinyint(1) DEFAULT 0, PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
ALTER TABLE `events` ADD COLUMN IF NOT EXISTS `poll_template` VARCHAR(200) NULL DEFAULT NULL AFTER `hide_raid_picture`;

ALTER TABLE `pokemon`
ADD COLUMN IF NOT EXISTS `pokemon_form_name` varchar(45) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `pokemon_form_id` int(4) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `asset_suffix` varchar(45) DEFAULT NULL,
DROP COLUMN IF EXISTS `pokemon_form`,
DROP COLUMN IF EXISTS `raid_level`,
ADD COLUMN IF NOT EXISTS `type` varchar(10) CHARACTER SET utf8mb4 DEFAULT '' AFTER `weather`,
ADD COLUMN IF NOT EXISTS `type2` varchar(10) CHARACTER SET utf8mb4 DEFAULT '' AFTER `type`,
CHANGE COLUMN `min_cp` `min_cp` int(10) unsigned NOT NULL DEFAULT 0,
CHANGE COLUMN `max_cp` `max_cp` int(10) unsigned NOT NULL DEFAULT 0,
CHANGE COLUMN `min_weather_cp` `min_weather_cp` int(10) unsigned NOT NULL DEFAULT 0,
CHANGE COLUMN `max_weather_cp` `max_weather_cp` int(10) unsigned NOT NULL DEFAULT 0,
CHANGE COLUMN `weather` `weather` int(10) unsigned NOT NULL DEFAULT 0;

ALTER TABLE `overview`
ADD COLUMN IF NOT EXISTS `chat_title` VARCHAR(128) NULL AFTER `message_id`,
ADD COLUMN IF NOT EXISTS `chat_username` VARCHAR(32) NULL AFTER `chat_title`,
ADD COLUMN IF NOT EXISTS `updated` DATE NULL AFTER `chat_username`;

ALTER TABLE `cleanup`
DROP COLUMN IF EXISTS `cleaned`,
ADD COLUMN IF NOT EXISTS `type` VARCHAR(20) NULL AFTER `message_id`,
ADD COLUMN IF NOT EXISTS `date_of_posting` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `type`;
TRUNCATE TABLE `cleanup`;

ALTER TABLE `attendance`
DROP COLUMN IF EXISTS `extra_instinct`,
ADD COLUMN IF NOT EXISTS remote tinyint(1) unsigned DEFAULT '0',
ADD COLUMN IF NOT EXISTS `can_invite` TINYINT UNSIGNED NULL DEFAULT '0' AFTER `want_invite`,
ADD COLUMN IF NOT EXISTS `want_invite` tinyint(1) unsigned DEFAULT 0 AFTER `alarm`,
CHANGE COLUMN IF EXISTS `extra_mystic` `extra_in_person` TINYINT UNSIGNED NULL DEFAULT '0' ,
CHANGE COLUMN IF EXISTS `extra_valor` `extra_alien` TINYINT UNSIGNED NULL DEFAULT '0';
