ALTER TABLE `attendance` ADD COLUMN IF NOT EXISTS `want_invite` tinyint(1) unsigned DEFAULT 0 AFTER `alarm`;
