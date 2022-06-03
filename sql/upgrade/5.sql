ALTER TABLE user_input CHANGE COLUMN IF EXISTS `user_id` `user_id` BIGINT(20) DEFAULT NULL AFTER `id`;

CREATE TABLE `photo_cache` (`id` varchar(100) NOT NULL, `unique_id` varchar(45) NOT NULL, `pokedex_id` int(10) DEFAULT NULL, `form_id` int(4) DEFAULT NULL, `raid_id` int(10) unsigned DEFAULT NULL, `ended` tinyint(1) DEFAULT NULL, `gym_id` int(10) unsigned DEFAULT NULL, `standalone` tinyint(1) NOT NULL DEFAULT '0', PRIMARY KEY (`unique_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
ALTER TABLE `cleanup` ADD COLUMN IF NOT EXISTS `media_unique_id` varchar(45) DEFAULT NULL AFTER `date_of_posting`;
CREATE UNIQUE INDEX IF NOT EXISTS `unique_chat_msg` ON `cleanup` (chat_id, message_id);

ALTER TABLE `raids` MODIFY `level` enum('1','2','3','4','5','6','7','X') DEFAULT NULL;
ALTER TABLE `raid_bosses` MODIFY `raid_level` enum('1','2','3','4','5','6','7','8','X') DEFAULT NULL;
