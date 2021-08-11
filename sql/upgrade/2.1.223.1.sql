ALTER TABLE `devbot`.`events` 
ADD COLUMN `allow_remote` TINYINT UNSIGNED NOT NULL DEFAULT 1 AFTER `hide_raid_picture`,
ADD COLUMN `allow_invite` TINYINT UNSIGNED NOT NULL DEFAULT 1 AFTER `allow_remote`;
