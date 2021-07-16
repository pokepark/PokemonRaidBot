 ALTER TABLE `users` add `trainername_time` DATETIME DEFAULT NULL AFTER `trainername`;
 ALTER TABLE `users` add `trainercode` varchar(12) CHARACTER SET utf8mb4 DEFAULT NULL AFTER `trainername_time`;
 ALTER TABLE `users` add `trainercode_time` DATETIME DEFAULT NULL AFTER `trainercode`;