ALTER TABLE `cleanup` DROP COLUMN IF EXISTS `cleaned`;
DELETE FROM `cleanup` WHERE chat_id='0' or message_id='0';
