ALTER TABLE `attendance` ADD COLUMN IF NOT EXISTS `want_invite` tinyint(1) unsigned DEFAULT 0 AFTER `alarm`;
CREATE TABLE IF NOT EXISTS `events` (
  `id` int(3) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(45) CHARACTER SET latin1 NOT NULL,
  `description` varchar(200) CHARACTER SET latin1 NOT NULL,
  `vote_key_mode` int(3) NOT NULL DEFAULT 0,
  `time_slots` int(3) DEFAULT NULL,
  `raid_duration` int(3) unsigned NOT NULL DEFAULT 0,
  `hide_raid_picture` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;

ALTER TABLE `raids` ADD COLUMN IF NOT EXISTS `event` int(3) DEFAULT NULL;
ALTER TABLE `raids` ADD COLUMN IF NOT EXISTS `event_note` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL;
