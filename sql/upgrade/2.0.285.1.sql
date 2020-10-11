 ALTER TABLE `users` add `trainername_time` DATETIME DEFAULT NULL AFTER `trainername`;
 ALTER TABLE `users` add `trainercode` varchar(12) CHARACTER SET utf8mb4 DEFAULT NULL AFTER `trainername_time`;
 ALTER TABLE `users` add `trainercode_time` DATETIME DEFAULT NULL AFTER `trainercode`;
 INSERT INTO pokemon (pokedex_id, pokemon_name, pokemon_form_name, pokemon_form_id, asset_suffix, min_cp, max_cp, min_weather_cp, max_weather_cp, weather, shiny) VALUES ('229','Houndoom','mega','-1','51','1432','1505','1790','1882','812','0') 
