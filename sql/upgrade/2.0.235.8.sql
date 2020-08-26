ALTER TABLE `pokemon` ADD `pokemon_form_name` varchar(45) DEFAULT NULL;
ALTER TABLE `pokemon` ADD `pokemon_form_id` int(4) DEFAULT NULL;
ALTER TABLE `pokemon` ADD `asset_suffix` varchar(45) DEFAULT NULL;
ALTER TABLE `pokemon` DROP `pokemon_form`;

ALTER TABLE `raids` MODIFY `pokemon` int(4) DEFAULT NULL;
ALTER TABLE `raids` ADD `pokemon_form` int(4) unsigned NOT NULL DEFAULT 0;
