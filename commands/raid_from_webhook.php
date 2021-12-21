<?php
// Write to log.
debug_log('RAID_FROM_WEBHOOK()');

if ($metrics){
    $webhook_raids_received_total = $metrics->registerCounter($prefix, 'webhook_raids_received_total', 'Total raids received via webhook');
    $webhook_raids_accepted_total = $metrics->registerCounter($prefix, 'webhook_raids_accepted_total', 'Total raids received & accepted via webhook');
    $webhook_raids_posted_total = $metrics->registerCounter($prefix, 'webhook_raids_posted_total', 'Total raids posted automatically');
}

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
if ($metrics){
    $webhook_raids_received_total->incBy(count($update));
}
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
                    gender = :gender,
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
                'gender' => $gender,
                'costume' => $costume,
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
                'gender' => $gender,
                'costume' => $costume
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
        if ($metrics){
            $webhook_raids_accepted_total->inc();
        }

        // Skip posting if create only -mode is set or raid time is greater than value set in config
        if ($config->WEBHOOK_CREATE_ONLY or ($raid['message']['end']-$raid['message']['start']) > ($config->WEBHOOK_EXCLUDE_AUTOSHARE_DURATION * 60) ) {
            debug_log($gym_name,'Not autoposting raid, WEBHOOK_CREATE_ONLY is set to true or raids duration is over the WEBHOOK_EXCLUDE_AUTOSHARE_DURATION threshold:');
            continue;
        }
    }

    // Query missing data needed to construct the raid poll
    $tz_diff = tz_diff();
    try {
        $query_missing = '
            SELECT      IF (:raid_pokemon = 0,
                            IF((SELECT  count(*)
                                FROM    raid_bosses
                                WHERE   raid_level = :raid_level
                                AND     scheduled = 1
                                AND     convert_tz(:raid_spawn,"+00:00","'.$tz_diff.'") BETWEEN date_start AND date_end) = 1,
                                (SELECT  pokedex_id
                                FROM    raid_bosses
                                WHERE   raid_level = :raid_level
                                AND     convert_tz(:raid_spawn,"+00:00","'.$tz_diff.'") BETWEEN date_start AND date_end
                                LIMIT   1),
                                (select concat(\'999\', :raid_level) as pokemon)
                                )
                        ,:raid_pokemon) as pokemon,
                        IF (:raid_pokemon = 0,
                            IF((SELECT  count(*) as count
                                FROM    raid_bosses
                                WHERE   raid_level = :raid_level
                                AND     scheduled = 1
                                AND     convert_tz(:raid_spawn,"+00:00","'.$tz_diff.'") BETWEEN date_start AND date_end) = 1,
                                (SELECT  pokemon_form_id
                                FROM    raid_bosses
                                WHERE   raid_level = :raid_level
                                AND     convert_tz(:raid_spawn,"+00:00","'.$tz_diff.'") BETWEEN date_start AND date_end
                                LIMIT   1),
                                \'0\'
                                ),
                            IF(:raid_pokemon_form = 0,
                                (SELECT pokemon_form_id FROM pokemon
                                WHERE
                                    pokedex_id = :raid_pokemon AND
                                    pokemon_form_name = \'normal\'
                                LIMIT 1), :raid_pokemon_form)
                            ) as pokemon_form,
            gyms.lat, gyms.lon, gyms.address, gyms.gym_name, gyms.ex_gym, gyms.gym_note,
            users.*,
            TIME_FORMAT(TIMEDIFF(:raid_end_time, UTC_TIMESTAMP()) + INTERVAL 1 MINUTE, \'%k:%i\') AS t_left
            FROM       gyms, (SELECT users.name, users.trainername, users.nick FROM users WHERE users.user_id = :user_id) as users
            WHERE      gyms.id = :gym_internal_id
            LIMIT 1
        ';
        $execute_array_missing = [
            'raid_pokemon' => $pokemon,
            'raid_pokemon_form' => $form,
            'raid_level' => $level,
            'raid_spawn' => $spawn,
            'raid_end_time' => $end,
            'user_id' => $config->WEBHOOK_CREATOR,
            'gym_internal_id' => $gym_internal_id,
        ];

        $statement_missing = $dbh->prepare($query_missing);
        $statement_missing->execute($execute_array_missing);
        $missing_raid_data = $statement_missing->fetch();

        // Combine resulting data with stuff received from webhook to create a complete raid array
        $raid = array_merge($missing_raid_data, [
                                                    'id' => $raid_id,
                                                    'user_id' => $config->WEBHOOK_CREATOR,
                                                    'spawn' => $spawn,
                                                    'start_time' => $start,
                                                    'end_time' => $end,
                                                    'gym_team' => $team,
                                                    'gym_id' => $gym_internal_id,
                                                    'level' => $level,
                                                    'move1' => $move_1,
                                                    'move2' => $move_2,
                                                    'gender' => $gender,
                                                    'costume' => $costume,
                                                    'event' => NULL,
                                                    'event_note' => NULL,
                                                    'event_name' => NULL,
                                                    'event_description' => NULL,
                                                    'event_vote_key_mode' => NULL,
                                                    'event_time_slots' => NULL,
                                                    'event_raid_duration' => NULL,
                                                    'event_hide_raid_picture' => NULL,
                                                    'event_poll_template' => NULL,
                                                ]);
    }
    catch (PDOException $exception) {
        error_log($exception->getMessage());
        $dbh = null;
        exit;
    }
    if($send_updates == true) {
        require_once(LOGIC_PATH .'/update_raid_poll.php');
        $update = update_raid_poll($raid_id, $raid, false, $tg_json, false); // update_raid_poll() will return false if the raid isn't shared to any chat
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
        if ($metrics){
            $webhook_raids_posted_total->inc();
        }
        $tg_json = send_raid_poll($raid_id, $raid, $chats, $tg_json);
    }
}

// Telegram multicurl request.
curl_json_multi_request($tg_json);

?>  
