ALTER TABLE raids ADD COLUMN IF NOT EXISTS `costume` SMALLINT UNSIGNED NULL DEFAULT 0 AFTER `gender`;

ALTER TABLE raid_bosses ADD COLUMN IF NOT EXISTS `scheduled` TINYINT(1) NULL DEFAULT 0 AFTER `raid_level`;

ALTER TABLE gyms ADD COLUMN IF NOT EXISTS `temporary_gym` TINYINT(1) UNSIGNED NULL DEFAULT 0 AFTER `img_url`;

ALTER TABLE users ADD COLUMN IF NOT EXISTS `auto_alarm` TINYINT(1) UNSIGNED NULL DEFAULT 0 AFTER `tutorial`;
