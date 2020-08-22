ALTER TABLE `pokemon` ADD `pokemon_form_name` varchar(45) DEFAULT NULL;
ALTER TABLE `pokemon` ADD `pokemon_form_id` int(4) DEFAULT NULL;
ALTER TABLE `pokemon` DROP `pokemon_form`;

ALTER TABLE `raids` MODIFY `pokemon` int(4) DEFAULT NULL;
ALTER TABLE `raids` ADD `pokemon_form` int(4) unsigned NOT NULL DEFAULT 0;
ALTER TABLE `raids` ADD `raid_level` enum('0','1','2','3','4','5','X') DEFAULT '0';
