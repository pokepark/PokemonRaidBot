ALTER TABLE `attendance` 
DROP COLUMN `extra_instinct`,
ADD COLUMN `can_invite` TINYINT UNSIGNED NULL DEFAULT '0' AFTER `want_invite`,
CHANGE COLUMN `extra_mystic` `extra_in_person` TINYINT UNSIGNED NULL DEFAULT '0' ,
CHANGE COLUMN `extra_valor` `extra_alien` TINYINT UNSIGNED NULL DEFAULT '0' ;