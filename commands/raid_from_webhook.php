<?php
// Write to log.
debug_log('RAID_FROM_WEBHOOK()');

foreach ($update as $raid) {

    $level = $raid['message']['level'];
    // TODO: Read from configuration
    if ( $level == 1 || $level == 2 || $level == 3 ) {
        
        continue;
    }

    // Create gym if not exists
    $gym_name = $data['message']['name'];
    $gym_lat = $data['message']['latitude'];
    $gym_lon = $data['message']['longitude'];
    $gym_id = $data['message']['gym_id'];
    $gym_img_url = $data['message']['url'];
    $gym_is_ex = $data['message']['is_ex_raid_eligible'];
    $gym_internal_id = 0;
    
    // Does gym exists?
    try {

        $query = '
            SELECT id, lat, lon, gym_name, ex_gym, img_url
            FROM gyms
            WHERE
                gym_id LIKE :gym_id
            LIMIT 1
        ';
        $statement = $dbh->prepare( $query );
        $statement->bindValue(':gym_id', $gym_id, PDO::PARAM_STR);
        $statement->execute();
        while ($row = $statement->fetch()) {
    
            // TODO: Check for data
            $gym_internal_id = $row['id'];
            
//            $gym_lat_exist = $row['lat'];
//            $gym_lob_exist = $row['lon'];
//            $gym_
        }
    }
    catch (PDOException $exception) {

        error_log($exception->getMessage());
        $dbh = null;
        exit;
    }
    // Update gym name in raid table.
    // TODO: Compare data and only update on change - so comment out to avoid traffic on the database
    if ($gym_internal_id > 0) {
        
/*        try {

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
            $statement->bindValue(':lat', $gym_lat, PDO::PARAM_STR);
            $statement->bindValue(':lon', $gym_lon, PDO::PARAM_STR);
            $statement->bindValue(':gym_name', $gym_id, PDO::PARAM_STR);
            $statement->bindValue(':ex_gym', $gym_is_ex, PDO::PARAM_INT);
            $statement->bindValue(':img_url', $gym_img_url, PDO::PARAM_STR);
            $statement->bindValue(':gym_id', $gym_id, PDO::PARAM_STR);
            $statement->execute();
        }
        catch (PDOException $exception) {

            error_log($exception->getMessage());
            $dbh = null;
            exit;
        } */
    }
    // Create gym
    else {
        
        try {

            $query = '
                
                INSERT INTO gyms (lat, lon, gym_name, gym_id, ex_gym, img_url, show_gym)
                VALUES (:lat, :lon, :gym_name, :gym_id, :ex_gym, :img_url, 1)
            ';
            $statement = $dbh->prepare( $query );
            $statement->bindValue(':lat', $gym_lat, PDO::PARAM_STR);
            $statement->bindValue(':lon', $gym_lon, PDO::PARAM_STR);
            $statement->bindValue(':gym_name', $gym_id, PDO::PARAM_STR);
            $statement->bindValue(':gym_id', $gym_id, PDO::PARAM_STR);
            $statement->bindValue(':ex_gym', $gym_is_ex, PDO::PARAM_INT);
            $statement->bindValue(':img_url', $gym_img_url, PDO::PARAM_STR);
            $statement->execute();
            $gym_internal_id = $dbh->lastInsertId();
        }
        catch (PDOException $exception) {

            error_log($exception->getMessage());
            $dbh = null;
            exit;
        }
    }

    // Create raid if not exists otherwise update if changes are detected
    $pokemon = $raid['message']['pokemon'];
    // Just an egg
    if ( $pokemon == 0 ) {
        
        switch ($level) {
            case (1):
                $pokemon = '9991';
                break;
            case (2):
                $pokemon = '9992';
                break;
            case (3):
                $pokemon = '9993';
                break;
            case (4):
                $pokemon = '9994';
                break;
            case (5):
                $pokemon = '9995';
                break;
            }
    }
    // TODO: Translate Form
    $form = 0;
    if ( isset($raid['message']['form']) ) {}
    // TODO: Translate Gender
    $gender = '';
    if ( isset($raid['message']['gender']) ) {}
    $move_1 = $raid['message']['move_1'];
    $move_2 = $raid['message']['move_2'];
    $pokemon = $pokemon . '-normal';
    $start_timestamp = $raid['message']['start'];
    $end_timestamp = $raid['message']['end'];
    $start = date("Y-m-d H:i:s",$start_timestamp);
    $end = date("Y-m-d H:i:s",$end_timestamp);

    // Insert new raid or update existing raid/ex-raid?
    $raid_id = raid_duplication_check($gym_indernal_id,$start, $end);
    
    // Raid exists, do updates!
    if ( $raid_id > 0 ) {
        
        continue;
    }
    
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
    
    // Create Raid and send messages
    try {

        $query = '
                
            INSERT INTO raids (pokemon, user_id, first_seen, start_time, end_time, gym_team, gym_id, move1, move2, gender)
            VALUES (:pokemon, :user_id, :first_seen, :start_time, :end_time, :gym_team, :gym_id, :move1, :move2, :gender)
        ';
        $statement = $dbh->prepare( $query );
        $statement->bindValue(':pokemon', $pokemon, PDO::PARAM_STR);
        $statement->bindValue(':user_id', RAID_AUTO_USER, PDO::PARAM_STR);
        $statement->bindValue(':first_seen', date("Y-m-d H:i:s"), PDO::PARAM_STR);
        $statement->bindValue(':start_time', $start, PDO::PARAM_STR);
        $statement->bindValue(':end_time', $end, PDO::PARAM_STR);
        $statement->bindValue(':gym_team', $team, PDO::PARAM_STR);
        $statement->bindValue(':gym_id', $gym_internal_id, PDO::PARAM_INT);
        $statement->bindValue(':move1', $move_1, PDO::PARAM_STR);
        $statement->bindValue(':move2', $move_2, PDO::PARAM_STR);
        $statement->bindValue(':gender', $gender, PDO::PARAM_STR);
        $statement->execute();
        $raid_id = $dbh->lastInsertId();
    }
    catch (PDOException $exception) {

        error_log($exception->getMessage());
        $dbh = null;
        exit;
    }
    
    // Get raid data.
    $created_raid = get_raid($raid_id);

    $chat_id = '';
    // Send location.
    if (RAID_LOCATION == true) {

        //$loc = send_location($update['message']['chat']['id'], $raid['lat'], $raid['lon']);
        $msg_text = !empty($created_raid['address']) ? $created_raid['address'] . ', ' . substr(strtoupper(BOT_ID), 0, 1) . '-ID = ' . $created_raid['id'] : $created_raid['pokemon'] . ', ' . $created_raid['id']; // DO NOT REMOVE " ID = " --> NEEDED FOR CLEANUP PREPARATION!
        $loc = send_venue($chat_id, $created_raid['lat'], $created_raid['lon'], "", $msg_text);

        // Write to log.
        debug_log('location:');
        debug_log($loc);
    }
    
    // Set text.
    $text = show_raid_poll($created_raid);
    
    // Set reply to.
    $reply_to = $chat_id; //$update['message']['chat']['id'];
    
    // Send the message.
    send_message($chat_id, $text, $keys, ['reply_to_message_id' => $reply_to, 'reply_markup' => ['selective' => true, 'one_time_keyboard' => true], 'disable_web_page_preview' => 'true']);
}
?>
