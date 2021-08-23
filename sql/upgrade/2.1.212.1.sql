ALTER TABLE `overview`
ADD COLUMN IF NOT EXISTS `chat_title` VARCHAR(128) NULL AFTER `message_id`,
ADD COLUMN IF NOT EXISTS `chat_username` VARCHAR(32) NULL AFTER `chat_title`,
ADD COLUMN IF NOT EXISTS `updated` DATE NULL AFTER `chat_username`;
