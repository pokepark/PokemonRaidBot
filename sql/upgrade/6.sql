ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `privileges` JSON NULL AFTER `auto_alarm`;
