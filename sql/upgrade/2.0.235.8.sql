ALTER TABLE `pokemon` ADD COLUMN IF NOT EXISTS `pokemon_form_name` varchar(45) DEFAULT NULL;
ALTER TABLE `pokemon` ADD COLUMN IF NOT EXISTS `pokemon_form_id` int(4) DEFAULT NULL;
ALTER TABLE `pokemon` ADD COLUMN IF NOT EXISTS `asset_suffix` varchar(45) DEFAULT NULL;
ALTER TABLE `pokemon` DROP COLUMN IF EXISTS `pokemon_form`;

ALTER TABLE `raids` MODIFY `pokemon` int(4) DEFAULT NULL;
ALTER TABLE `raids` ADD COLUMN IF NOT EXISTS `pokemon_form` int(4) unsigned NOT NULL DEFAULT 0;
