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
info_log(count($update),"Received raids:");
foreach ($update as $raid) {
    $level = $raid['message']['level'];
    $pokemon = $raid['message']['pokemon_id'];
    $exclude_raid_levels = explode(',', $config->WEBHOOK_EXCLUDE_RAID_LEVEL);
    $exclude_pokemons = explode(',', $config->WEBHOOK_EXCLUDE_POKEMON);
    if ((!empty($level) && in_array($level, $exclude_raid_levels)) || (!empty($pokemon) && in_array($pokemon, $exclude_pokemons))) {
        info_log($pokemon.' Tier: '.$level,'Ignoring raid, the pokemon or raid level is excluded:');
        continue;
    }

    $gym_name = $raid['message']['name'];
    if ($config->WEBHOOK_EXCLUDE_UNKNOWN && $gym_name === 'unknown') {
        info_log($raid['message']['gym_id'],'Ignoring raid, the gym name is unknown and WEBHOOK_EXCLUDE_UNKNOWN says to ignore. id:');
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
                info_log($geofence_id,'Raid inside geofence:');
            }
        }
        if ($insideGeoFence === false) {
            info_log($gym_name,'Ignoring raid, not inside any geofence:');
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
            info_log($gym_internal_id, 'New gym '.$gym_name.' created with internal id of:');
        }else {
            $statement = $dbh->prepare('SELECT id FROM gyms WHERE gym_id LIKE :gym_id LIMIT 1');
            $statement->execute(['gym_id'=>$gym_id]);
            $gym_internal_id = $statement->fetch()['id'];
            info_log($gym_internal_id, 'Gym info updated. Internal id:');
        }
    }
    catch (PDOException $exception) {
        error_log($exception->getMessage());
        $dbh = null;
        exit;
    }

    // Create raid if not exists otherwise update if changes are detected
    // Just an egg
    if ($pokemon == 0) {
        $pokemon = '999' . $level;
    }

    $form = 0;
    $form_lookup = false;
    $form_query = ':pokemon_form';
    // Use negated evolution id instead of form id if present
    if(isset($raid['message']['evolution']) && $raid['message']['evolution'] > 0) {
        $form = 0 - $raid['message']['evolution'];
    }else {
        if ( isset($raid['message']['form']) && $raid['message']['form'] != '0') {
            // Use the form provided in webhook if it's valid
            $form = $raid['message']['form'];
        }elseif($pokemon != 0) {
            // Else look up the normal form's id from pokemon table unless it's an egg
            $form_query = '(SELECT pokemon_form_id FROM pokemon
            WHERE
                pokedex_id = :pokemon AND
                pokemon_form_name = \'normal\'
            LIMIT 1)';
            $form_lookup = true;
        }
    }

    // Raid pokemon gender
    $gender = 0;
    if ( isset($raid['message']['gender']) ) {
        $gender = $raid['message']['gender'];
    }

    // Raid pokemon moveset
    $move_1 = 0;
    $move_2 = 0;
    if ($pokemon < 9900) {
       $move_1 = $raid['message']['move_1'];
       $move_2 = $raid['message']['move_2'];
    }

    // Raid start and endtimes
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
                    pokemon_form = '.$form_query.',
                    gym_team = :gym_team,
                    move1 = :move1,
                    move2 = :move2,
                    gender = :gender
                WHERE
                    id LIKE :id
            ';
            $execute_array = [
                'pokemon' => $pokemon,
                'gym_team' => $team,
                'move1' => $move_1,
                'move2' => $move_2,
                'gender' => $gender,
                'id' => $raid_id
            ];
            if(!$form_lookup) $execute_array['pokemon_form'] =  $form;
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
            info_log($raid_id, 'Raid updated:');
        }else {
            info_log($gym_name,'Nothing was updated, moving on:');
            continue;
        }
    }else {
        // Create Raid and send messages
        try {
            $query = '
                INSERT INTO raids (pokemon, pokemon_form, user_id, first_seen, start_time, end_time, gym_team, gym_id, move1, move2, gender)
                VALUES (:pokemon, '.$form_query.', :user_id, :first_seen, :start_time, :end_time, :gym_team, :gym_id, :move1, :move2, :gender)
            ';
            $execute_array = [
                'pokemon' => $pokemon,
                'user_id' => $config->WEBHOOK_CREATOR,
                'first_seen' => gmdate('Y-m-d H:i:s'),
                'start_time' => $start,
                'end_time' => $end,
                'gym_team' => $team,
                'gym_id' => $gym_internal_id,
                'move1' => $move_1,
                'move2' => $move_2,
                'gender' => $gender
            ];
            if(!$form_lookup) $execute_array['pokemon_form'] = $form;
            $statement = $dbh->prepare( $query );
            $statement->execute($execute_array);
            $raid_id = $dbh->lastInsertId();
            info_log($raid_id, 'New raid created, raid id:');
        }
        catch (PDOException $exception) {
            error_log($exception->getMessage());
            $dbh = null;
            exit;
        }

        // Skip posting if create only -mode is set or raid time is greater than value set in config
        if ($config->WEBHOOK_CREATE_ONLY or ($raid['message']['end']-$raid['message']['start']) > ($config->WEBHOOK_EXCLUDE_AUTOSHARE_DURATION * 60) ) {
            info_log($gym_name,'Not autoposting raid, WEBHOOK_CREATE_ONLY is set to true or raids duration is over the WEBHOOK_EXCLUDE_AUTOSHARE_DURATION threshold:');
            continue;
        }
    }

    // Get raid data.
    $raid = get_raid($raid_id);

    // Set text.
    $text = show_raid_poll($raid);

    // Set keys.
    $keys = keys_vote($raid);

    if($send_updates == true) {
        $cleanup_query = '
            SELECT    *
            FROM      cleanup
                WHERE   raid_id = :id
        ';
        $cleanup_statement = $dbh->prepare( $cleanup_query );
        $cleanup_statement->execute(['id' => $raid_id]);
        while ($row = $cleanup_statement->fetch()) {
            if($config->RAID_PICTURE) {
                require_once(LOGIC_PATH . '/raid_picture.php');
                $picture_url = raid_picture_url($raid);
                $tg_json[] = editMessageMedia($row['message_id'], $text['short'], $keys, $row['chat_id'], ['disable_web_page_preview' => 'true'],true, $picture_url);
            }else {
                $tg_json[] = editMessageText($row['message_id'], $text['full'], $keys, $row['chat_id'], ['disable_web_page_preview' => 'true'],true);
            }
        }
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
        if(!empty($config->WEBHOOK_CHATS)) {
           $webhook_chats = explode(',', $config->WEBHOOK_CHATS);
        }
        $chats = array_merge($chats_geofence, $chats_raidlevel, $webhook_chats);

        // Post raid polls.
        foreach ($chats as $chat) {
            info_log('Posting poll to chat: ' . $chat);

            // Send location.
            if ($config->RAID_LOCATION) {

                $msg_text = !empty($raid['address']) ? $raid['address'] . ', ' . substr(strtoupper($config->BOT_ID), 0, 1) . '-ID = ' . $raid['id'] : $raid['pokemon'] . ', ' . substr(strtoupper($config->BOT_ID), 0, 1) . '-ID = ' . $raid['id']; // DO NOT REMOVE ' ID = ' --> NEEDED FOR $config->CLEANUP PREPARATION!
                $loc = send_venue($chat, $raid['lat'], $raid['lon'], '', $msg_text, true);
                $tg_json[] = $loc;
                info_log($loc, 'Location:');
            }

            // Set reply to.
            $reply_to = $chat;

            // Send the message.
            if($config->RAID_PICTURE) {
                require_once(LOGIC_PATH . '/raid_picture.php');
                $picture_url = raid_picture_url($raid);
                $tg_json[] = send_photo($chat, $picture_url, $text['short'], $keys, ['disable_web_page_preview' => 'true'], true);
            } else {
                $tg_json[] = send_message($chat, $text['full'], $keys, ['disable_web_page_preview' => 'true'], true);
            }
        }
    }
}

// Telegram multicurl request.
curl_json_multi_request($tg_json);

?>
