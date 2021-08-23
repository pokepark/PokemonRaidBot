ALTER TABLE `events` ADD COLUMN IF NOT EXISTS `poll_template` VARCHAR(200) NULL DEFAULT NULL AFTER `hide_raid_picture`;
