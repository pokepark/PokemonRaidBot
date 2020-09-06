ALTER TABLE `users` add `trainercode` varchar(12) CHARACTER SET utf8mb4 DEFAULT NULL AFTER `setname_time`;
ALTER TABLE `users` add `setcode_time` DATETIME DEFAULT NULL AFTER `trainercode`;