ALTER TABLE `raids` CHANGE COLUMN `first_seen` `spawn` DATETIME NULL DEFAULT NULL;
ALTER TABLE `raids` ADD COLUMN `level` enum('1','2','3','4','5','6','X') DEFAULT NULL AFTER `gym_id`;
CREATE TABLE `raid_bosses` (`id` int(11) NOT NULL AUTO_INCREMENT,`pokedex_id` int(10) DEFAULT NULL,`pokemon_form_id` int(4) DEFAULT NULL,`date_start` datetime NOT NULL DEFAULT '1970-01-01 00:00:01',`date_end` datetime NOT NULL DEFAULT '2038-01-19 03:14:07',`raid_level` enum('1','2','3','4','5','6','X') DEFAULT NULL,PRIMARY KEY (`id`)) ENGINE=InnoDB AUTO_INCREMENT=81 DEFAULT CHARSET=utf8mb4;
INSERT INTO `raid_bosses` (`pokedex_id`,`pokemon_form_id`,`raid_level`) SELECT `pokedex_id`,`pokemon_form_id`,`raid_level` FROM pokemon WHERE raid_level != '0';
ALTER TABLE `pokemon` DROP COLUMN `raid_level`;
