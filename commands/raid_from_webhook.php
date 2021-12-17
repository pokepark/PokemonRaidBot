<?php
// Write to log.
debug_log('RAID_FROM_WEBHOOK()');

function isPointInsidePolygon($point, $vertices) {
    $i = 0;
    $j = 0;
    $c = 0;
    $count_vertices = count($vertices);
    for ($i = 0, $j = $count_vertices-1 ; $i < $count_vertices; $j = $i++) {
        if ((($vertices[$i]['y'] > $point['y'] != ($vertices[$j]['y'] > $point['y'])) && ($point['x'] < ($vertices[$j]['x'] - $vertices[$i]['x']) * ($point['y'] - $vertices[$i]['y']) / ($vertices[$j]['y'] - $vertices[$i]['y']) + $vertices[$i]['x']) ) ) {
            $c = !$c;
        }
    }
    return $c;
}
// Geofences
$geofences = false;
if (file_exists(CONFIG_PATH . '/geoconfig.json')) {
    $raw = file_get_contents(CONFIG_PATH . '/geoconfig.json');
    $geofences = json_decode($raw, true);
    $geofence_polygons = [];
    foreach($geofences as $geofence) {
        foreach ($geofence['path'] as $geopoint) {
            $geofence_polygons[$geofence['id']][] = ['x' => $geopoint[0], 'y' => $geopoint[1]];
        }
    }
}

// Telegram JSON array.
$tg_json = [];
debug_log(count($update),"Received raids:");
foreach ($update as $raid) {
    $level = $raid['message']['level'];
    $pokemon = $raid['message']['pokemon_id'];
    $exclude_raid_levels = explode(',', $config->WEBHOOK_EXCLUDE_RAID_LEVEL);
    $exclude_pokemons = explode(',', $config->WEBHOOK_EXCLUDE_POKEMON);
    if ((!empty($level) && in_array($level, $exclude_raid_levels)) || (!empty($pokemon) && in_array($pokemon, $exclude_pokemons))) {
        debug_log($pokemon.' Tier: '.$level,'Ignoring raid, the pokemon or raid level is excluded:');
        continue;
    }

    $gym_name = $raid['message']['name'];
    if ($config->WEBHOOK_EXCLUDE_UNKNOWN && $gym_name === 'unknown') {
        debug_log($raid['message']['gym_id'],'Ignoring raid, the gym name is unknown and WEBHOOK_EXCLUDE_UNKNOWN says to ignore. id:');
        continue;
    }
    $gym_lat = $raid['message']['latitude'];
    $gym_lon = $raid['message']['longitude'];
    $gym_id = $raid['message']['gym_id'];
    $gym_img_url = $raid['message']['url'];
    $gym_is_ex = ( $raid['message']['is_ex_raid_eligible'] ? 1 : 0 );
    $gym_internal_id = 0;

    // Check geofence, if available, and skip current raid if not inside any fence
    if ($geofences != false) {
        $insideGeoFence = false;
        $inside_geofences = [];
        $point = ['x' => $gym_lat, 'y' => $gym_lon];
        foreach ($geofence_polygons as $geofence_id => $polygon) {
            if (isPointInsidePolygon($point, $polygon)) {
                $inside_geofences[] = $geofence_id;
                $insideGeoFence = true;
                debug_log($geofence_id,'Raid inside geofence:');
            }
        }
        if ($insideGeoFence === false) {
            debug_log($gym_name,'Ignoring raid, not inside any geofence:');
            continue;
        }
    }

    // Create gym if it doesn't exists, otherwise update gym info.
    try {
        $query = '
            INSERT INTO gyms (lat, lon, gym_name, gym_id, ex_gym, img_url, show_gym)
            VALUES (:lat, :lon, :gym_name, :gym_id, :ex_gym, :img_url, 1)
            ON DUPLICATE KEY UPDATE                     
                lat = :lat,
                lon = :lon,
                gym_name = :gym_name,
                ex_gym = :ex_gym,
                img_url = :img_url
        ';
        $statement = $dbh->prepare( $query );
        $statement->execute([
            'lat' => $gym_lat,
            'lon' => $gym_lon,
            'gym_name' => $gym_name,
            'gym_id' => $gym_id,
            'ex_gym' => $gym_is_ex,
            'img_url' => $gym_img_url
        ]);
        if($statement->rowCount() == 1) {
            $gym_internal_id = $dbh->lastInsertId();
            debug_log($gym_internal_id, 'New gym '.$gym_name.' created with internal id of:');
        }else {
            $statement = $dbh->prepare('SELECT id FROM gyms WHERE gym_id LIKE :gym_id LIMIT 1');
            $statement->execute(['gym_id'=>$gym_id]);
            $gym_internal_id = $statement->fetch()['id'];
            debug_log($gym_internal_id, 'Gym info updated. Internal id:');
        }
    }
    catch (PDOException $exception) {
        error_log($exception->getMessage());
        $dbh = null;
        exit;
    }

    // Create raid if not exists otherwise update if changes are detected

    // Raid pokemon form
    // Use negated evolution id instead of form id if present
    if(isset($raid['message']['evolution']) && $raid['message']['evolution'] > 0) {
        $form = 0 - $raid['message']['evolution'];
    }else {
        $form = isset($raid['message']['form']) ? $raid['message']['form'] : 0;
    }

    // Raid pokemon gender
    $gender = 0;
    if ( isset($raid['message']['gender']) ) {
        $gender = $raid['message']['gender'];
    }
    // Raid pokemon costume
    $costume = 0;
    if ( isset($raid['message']['costume']) ) {
        $costume = $raid['message']['costume'];
    }

    // Raid pokemon moveset
    $move_1 = 0;
    $move_2 = 0;
    if ($pokemon < 9900) {
       $move_1 = $raid['message']['move_1'];
       $move_2 = $raid['message']['move_2'];
    }

    // Raid start and endtimes
    $spawn = (isset($raid['message']['spawn'])) ? gmdate('Y-m-d H:i:s',$raid['message']['spawn']) : gmdate('Y-m-d H:i:s', ($raid['message']['start'] - $config->RAID_EGG_DURATION*60));
    $start = gmdate('Y-m-d H:i:s',$raid['message']['start']);
    $end = gmdate('Y-m-d H:i:s',$raid['message']['end']);

    // Gym team
    $team = $raid['message']['team_id'];
    if (! empty($team)) {
        switch ($team) {
            case (1):
                $team = 'mystic';
                break;
            case (2):
                $team = 'valor';
                break;
            case (3):
                $team = 'instinct';
                break;
        }
    }

    // Insert new raid or update existing raid/ex-raid?
    $raid_id = active_raid_duplication_check($gym_internal_id);

    $send_updates = false;

    // Raid exists, do updates!
    if ( $raid_id > 0 ) {
        // Update database
        try {
            $query = '
                UPDATE raids
                SET
                    pokemon = :pokemon,
                    pokemon_form = :pokemon_form,
                    gym_team = :gym_team,
                    move1 = :move1,
                    move2 = :move2,
<<<<<<< HEAD
<<<<<<< HEAD
		    gender = :gender,
=======
                    gender = :gender,
>>>>>>> 974937cda6355f2a888cf0c1ac394214e6980f17
=======
                    gender = :gender,
>>>>>>> 974937cda6355f2a888cf0c1ac394214e6980f17
                    costume = :costume
                WHERE
                    id = :id
            ';
            $execute_array = [
                'pokemon' => $pokemon,
                'pokemon_form' => $form,
                'gym_team' => $team,
                'move1' => $move_1,
                'move2' => $move_2,
<<<<<<< HEAD
		'gender' => $gender,
		'costume' => $costume,
=======
                'gender' => $gender,
                'costume' => $costume,
<<<<<<< HEAD
>>>>>>> 974937cda6355f2a888cf0c1ac394214e6980f17
=======
>>>>>>> 974937cda6355f2a888cf0c1ac394214e6980f17
                'id' => $raid_id
            ];
            $statement = $dbh->prepare( $query );
            $statement->execute($execute_array);
        }
        catch (PDOException $exception) {
            error_log($exception->getMessage());
            $dbh = null;
            exit;
        }
        // If update was needed, send them to TG
        if($statement->rowCount() > 0) {
            $send_updates = true;
            debug_log($raid_id, 'Raid updated:');
        }else {
            debug_log($gym_name,'Nothing was updated, moving on:');
            continue;
        }
    }else {
        // Create Raid and send messages
        try {
            $query = '
                INSERT INTO raids (pokemon, pokemon_form, user_id, spawn, start_time, end_time, gym_team, gym_id, level, move1, move2, gender, costume)
                VALUES (:pokemon, :pokemon_form, :user_id, :spawn, :start_time, :end_time, :gym_team, :gym_id, :level, :move1, :move2, :gender, :costume)
            ';
            $execute_array = [
                'pokemon' => $pokemon,
                'pokemon_form' => $form,
                'user_id' => $config->WEBHOOK_CREATOR,
                'spawn' => $spawn,
                'start_time' => $start,
                'end_time' => $end,
                'gym_team' => $team,
                'gym_id' => $gym_internal_id,
                'level' => $level,
                'move1' => $move_1,
                'move2' => $move_2,
<<<<<<< HEAD
<<<<<<< HEAD
		'gender' => $gender,
		'costume' => $costume
=======
                'gender' => $gender,
                'costume' => $costume
>>>>>>> 974937cda6355f2a888cf0c1ac394214e6980f17
=======
                'gender' => $gender,
                'costume' => $costume
>>>>>>> 974937cda6355f2a888cf0c1ac394214e6980f17
            ];
            $statement = $dbh->prepare( $query );
            $statement->execute($execute_array);
            $raid_id = $dbh->lastInsertId();
            debug_log($raid_id, 'New raid created, raid id:');
        }
        catch (PDOException $exception) {
            error_log($exception->getMessage());
            $dbh = null;
            exit;
        }

        // Skip posting if create only -mode is set or raid time is greater than value set in config
        if ($config->WEBHOOK_CREATE_ONLY or ($raid['message']['end']-$raid['message']['start']) > ($config->WEBHOOK_EXCLUDE_AUTOSHARE_DURATION * 60) ) {
            debug_log($gym_name,'Not autoposting raid, WEBHOOK_CREATE_ONLY is set to true or raids duration is over the WEBHOOK_EXCLUDE_AUTOSHARE_DURATION threshold:');
            continue;
        }
    }

    if($send_updates == true) {
        require_once(LOGIC_PATH .'/update_raid_poll.php');
        $update = update_raid_poll($raid_id, false, false, $tg_json, false); // update_raid_poll() will return false if the raid isn't shared to any chat
        if($update != false) $tg_json = $update;
    }else {
        // Get chats to share to by raid level and geofence id
        $chats_geofence = [];
        if($geofences != false) {
            foreach ($inside_geofences as $geofence_id) {
                $const_geofence = 'WEBHOOK_CHATS_LEVEL_' . $level . '_' . $geofence_id;
                $const_geofence_chats = $config->{$const_geofence};

                if(!empty($const_geofence_chats)) {
                    $chats_geofence = explode(',', $const_geofence_chats);
                }
            }
        }

        // Get chats to share to by raid level 
        $const = 'WEBHOOK_CHATS_LEVEL_' . $level;
        $const_chats = $config->{$const};

        $chats_raidlevel =[];
        if(!empty($const_chats)) {
            $chats_raidlevel = explode(',', $const_chats);
        }

        // Get chats
        $webhook_chats = [];
        if(!empty($config->WEBHOOK_CHATS_ALL_LEVELS)) {
           $webhook_chats = explode(',', $config->WEBHOOK_CHATS_ALL_LEVELS);
        }
        $chats = array_merge($chats_geofence, $chats_raidlevel, $webhook_chats);

        require_once(LOGIC_PATH .'/send_raid_poll.php');
        $tg_json = send_raid_poll($raid_id, false, $chats, $tg_json);
    }
}

// Telegram multicurl request.
curl_json_multi_request($tg_json);

?>  
