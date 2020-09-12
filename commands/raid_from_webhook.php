<?php
// Write to log.
debug_log('RAID_FROM_WEBHOOK()');

function pointStringToCoordinates($pointString) {

    $coordinates = explode(" ", $pointString);
    return array("x" => $coordinates[0], "y" => $coordinates[1]);
}

function isPointInsidePolygon($point, $polygon) {

    $point = pointStringToCoordinates($point);
    $vertices = array();
    foreach ($polygon as $vertex) {

      $vertices[] = pointStringToCoordinates($vertex);
    }

    $i = 0;
    $j = 0;
    $c = 0;
    for ($i = 0, $j = count($vertices)-1 ; $i < count($vertices); $j = $i++) {

        if ((($vertices[$i]['y'] > $point['y'] != ($vertices[$j]['y'] > $point['y'])) && ($point['x'] < ($vertices[$j]['x'] - $vertices[$i]['x']) * ($point['y'] - $vertices[$i]['y']) / ($vertices[$j]['y'] - $vertices[$i]['y']) + $vertices[$i]['x']) ) ) {

            $c = !$c;
        }
    }
    return $c;
}

// Telegram JSON array.
$tg_json = array();

foreach ($update as $raid) {

    $level = $raid['message']['level'];
    $pokemon = $raid['message']['pokemon_id'];
    $exclude_raid_levels = explode(',', $config->WEBHOOK_EXCLUDE_RAID_LEVEL);
    $exclude_pokemons = explode(',', $config->WEBHOOK_EXCLUDE_POKEMON);
    if ((!empty($level) && in_array($level, $exclude_raid_levels)) || (!empty($pokemon) && in_array($pokemon, $exclude_pokemons))) {

        continue;
    }

    // Create gym if not exists
    $gym_name = $raid['message']['name'];
    if ($config->WEBHOOK_EXCLUDE_UNKNOWN && $gym_name === "unknown") {

        continue;
    }
    $gym_lat = $raid['message']['latitude'];
    $gym_lon = $raid['message']['longitude'];
    $gym_id = $raid['message']['gym_id'];
    $gym_img_url = $raid['message']['url'];
    $gym_is_ex = ( $raid['message']['is_ex_raid_eligible'] ? 1 : 0 );
    $gym_internal_id = 0;

    // Check geofence, if available and continue if not inside any fence
    if (file_exists(CONFIG_PATH . '/geoconfig.json')) {

        $insideGeoFence = false;
        $raw = file_get_contents(CONFIG_PATH . '/geoconfig.json');
        $geofences = json_decode($raw, true);
        foreach ($geofences as $geofence) {

            // if current raid inside path, add chats
            $point = $gym_lat . " " . $gym_lon;
            $polygon = array();
            foreach ($geofence['path'] as $geopoint) {

                array_push($polygon, "$geopoint[0] $geopoint[1]");
            }
            if (isPointInsidePolygon($point, $polygon)) {

                $insideGeoFence = true;
                break;
            }
        }
        if ($insideGeoFence === false) {

            continue;
        }
    }

    try {

        $query = '
            SELECT id
            FROM gyms
            WHERE
                gym_id LIKE :gym_id
            LIMIT 1
        ';
        $statement = $dbh->prepare( $query );
        $statement->execute(['gym_id' => $gym_id]);
        while ($row = $statement->fetch()) {

            $gym_internal_id = $row['id'];
        }
    }
    catch (PDOException $exception) {

        error_log($exception->getMessage());
        $dbh = null;
        exit;
    }
    // Update gym info in raid table.
    if ($gym_internal_id > 0) {

        try {

            $query = '
                UPDATE gyms
                SET
                    lat = :lat,
                    lon = :lon,
                    gym_name = :gym_name,
                    ex_gym = :ex_gym,
                    img_url = :img_url,
                    show_gym = 1
                WHERE
                    gym_id LIKE :gym_id
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
        }
        catch (PDOException $exception) {

            error_log($exception->getMessage());
            $dbh = null;
            exit;
        }
    }
    // Create gym
    else {

        try {

            $query = '

                INSERT INTO gyms (lat, lon, gym_name, gym_id, ex_gym, img_url, show_gym)
                VALUES (:lat, :lon, :gym_name, :gym_id, :ex_gym, :img_url, 1)
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
            $gym_internal_id = $dbh->lastInsertId();
        }
        catch (PDOException $exception) {

            error_log($exception->getMessage());
            $dbh = null;
            exit;
        }
    }

    // Create raid if not exists otherwise update if changes are detected
    // Just an egg
    if ($pokemon == 0) {
        $pokemon = '999' . $level;
    }

    $form = 0;
    // Use negated evolution id instead of form id if present
    if(isset($raid['message']['evolution']) && $raid['message']['evolution'] > 0) {
        $form = 0 - $raid['message']['evolution'];
    }else {
        if ( isset($raid['message']['form']) && $raid['message']['form'] != "0") {
            // Use the form provided in webhook if it's valid
            $form = $raid['message']['form'];
        }elseif($pokemon != 0) {
            // Else look up the normal form's id from pokemon table unless it's an egg
            try {
                $query = "
                    SELECT pokemon_form_id FROM pokemon
                    WHERE
                        pokedex_id = :pokemon AND
                        pokemon_form_name = 'normal'
                    LIMIT 1
                ";
                $statement = $dbh->prepare( $query );
                $statement->execute([
                  'pokemon' => $pokemon
              ]);
            }
            catch (PDOException $exception) {
                error_log($exception->getMessage());
                $dbh = null;
                exit;
            }
            $result = $statement->fetch();
            $form = $result['pokemon_form_id'];
        }
    }
    
    $gender = 0;
    if ( isset($raid['message']['gender']) ) {

        $gender = $raid['message']['gender'];
    }
    $move_1 = 0;
    $move_2 = 0;
    if ($pokemon < 9900) {

       $move_1 = $raid['message']['move_1'];
       $move_2 = $raid['message']['move_2'];
    }
    $start_timestamp = $raid['message']['start'];
    $end_timestamp = $raid['message']['end'];
    $start = gmdate("Y-m-d H:i:s",$start_timestamp);
    $end = gmdate("Y-m-d H:i:s",$end_timestamp);
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
                    gender = :gender
                WHERE
                    id LIKE :id
            ';
            $statement = $dbh->prepare( $query );
            $statement->execute([
              'pokemon' => $pokemon,
              'pokemon_form' => $form,
              'gym_team' => $team,
              'move1' => $move_1,
              'move2' => $move_2,
              'gender' => $gender,
              'id' => $raid_id
          ]);
        }
        catch (PDOException $exception) {
            error_log($exception->getMessage());
            $dbh = null;
            exit;
        }
        // If update was needed, send them to TG
        if($statement->rowCount() > 0) {
            // Get raid info for updating
            $raid_info = get_raid($raid_id);

            // Raid picture
            if($config->RAID_PICTURE) {
              require_once(LOGIC_PATH . '/raid_picture.php');
              $picture_url = raid_picture_url($raid_info);
            }

            $updated_msg = show_raid_poll($raid_info);
            $updated_keys = keys_vote($raid_info);

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
                    $picture_url = raid_picture_url($raid_info);
                    $tg_json[] = editMessageMedia($row['message_id'], $updated_msg['short'], $updated_keys, $row['chat_id'], ['disable_web_page_preview' => 'true'],true, $picture_url);
                }else {
                    $tg_json[] = editMessageText($row['message_id'], $updated_msg['full'], $updated_keys, $row['chat_id'], ['disable_web_page_preview' => 'true'],true);
                }
            }
        }
        continue;
    }

    // Create Raid and send messages
    try {
        $query = '

            INSERT INTO raids (pokemon, pokemon_form, user_id, first_seen, start_time, end_time, gym_team, gym_id, move1, move2, gender)
            VALUES (:pokemon, :pokemon_form, :user_id, :first_seen, :start_time, :end_time, :gym_team, :gym_id, :move1, :move2, :gender)
        ';
        $statement = $dbh->prepare( $query );
        $statement->execute([
          'pokemon' => $pokemon,
          'pokemon_form' => $form,
          'user_id' => $config->WEBHOOK_CREATOR,
          'first_seen' => gmdate("Y-m-d H:i:s"),
          'start_time' => $start,
          'end_time' => $end,
          'gym_team' => $team,
          'gym_id' => $gym_internal_id,
          'move1' => $move_1,
          'move2' => $move_2,
          'gender' => $gender
        ]);
        $raid_id = $dbh->lastInsertId();
    }
    catch (PDOException $exception) {

        error_log($exception->getMessage());
        $dbh = null;
        exit;
    }

    if ($config->WEBHOOK_CREATE_ONLY) {

        continue;
    }

    // Get raid data.
    $created_raid = get_raid($raid_id);

    // Set text.
    $text = show_raid_poll($created_raid);

    // Set keys.
    $keys = keys_vote($created_raid);

    // Get chats
    $chats = explode(',', $config->WEBHOOK_CHATS);

    for($i = 1; $i <= 6; $i++) {

        $const = 'WEBHOOK_CHATS_LEVEL_' . $i;
        $const_chats = $config->{$const};

        // Get geofence chats and geofences
        if(is_file(CONFIG_PATH . '/geoconfig.json')) {
            $raw = file_get_contents(CONFIG_PATH . '/geoconfig.json');
            $geofences = json_decode($raw, true);
            foreach ($geofences as $geofence) {

                $const_geofence = 'WEBHOOK_CHATS_LEVEL_' . $i . '_' . $geofence['id'];
                $const_geofence_chats = $config->{$const_geofence};

                // Debug
                //debug_log($const_geofence,'CONSTANT NAME:');
                //debug_log($const_geofence_chats),'CONSTANT VALUE:');

                // if current raid inside path, add chats
                $point = $created_raid['lat'] . " " . $created_raid['lon'];
                $polygon = array();
                foreach ($geofence['path'] as $geopoint) {

                    array_push($polygon, "$geopoint[0] $geopoint[1]");
                }

                if (isPointInsidePolygon($point, $polygon)) {

                    if($level == $i && !empty($const_geofence_chats)) {

                        $chats = explode(',', $const_geofence_chats);
                    }
                }
            }
        }
        // Debug.
        //debug_log($const,'CONSTANT NAME:');
        //debug_log($const_chats),'CONSTANT VALUE:');

        if($level == $i && !empty($const_chats)) {

            $chats = explode(',', $const_chats);
        }
    }


    // Post raid polls.
    foreach ($chats as $chat) {
        debug_log('Posting poll to chat: ' . $chat);

        // Send location.
        if ($config->RAID_LOCATION) {

            $msg_text = !empty($created_raid['address']) ? $created_raid['address'] . ', ' . substr(strtoupper($config->BOT_ID), 0, 1) . '-ID = ' . $created_raid['id'] : $created_raid['pokemon'] . ', ' . substr(strtoupper($config->BOT_ID), 0, 1) . '-ID = ' . $created_raid['id']; // DO NOT REMOVE " ID = " --> NEEDED FOR $config->CLEANUP PREPARATION!
            $loc = send_venue($chat, $created_raid['lat'], $created_raid['lon'], "", $msg_text, true);
            $tg_json[] = $loc;
            // Write to log.
            debug_log('location:');
            debug_log($loc);
        }

        // Set reply to.
        $reply_to = $chat; //$update['message']['chat']['id'];

        // Send the message.
        if($config->RAID_PICTURE) {
            require_once(LOGIC_PATH . '/raid_picture.php');
            $picture_url = raid_picture_url($created_raid);
            $tg_json[] = send_photo($chat, $picture_url, $text['short'], $keys, ['reply_to_message_id' => $reply_to, 'reply_markup' => ['selective' => true, 'one_time_keyboard' => true], 'disable_web_page_preview' => 'true'], true);
        } else {
            $tg_json[] = send_message($chat, $text['full'], ['inline_keyboard' => $keys, 'selective' => true, 'one_time_keyboard' => true], ['disable_web_page_preview' => 'true'], true);
        }
    }
}

// Telegram multicurl request.
curl_json_multi_request($tg_json);

?>
