<?php
// Write to log.
debug_log('ADDGYM()');

// For debug.
// debug_log($update);
// debug_log($data);

// Check access.
bot_access_check($update, 'gym-add');

// Get gym name.
$input = trim(substr($update['message']['text'], 7));

// Count commas given in input.
$count = substr_count($input, ",");

// 1 comma as it should be?
// E.g. 52.5145434,13.3501189
if($count == 1) {
    $lat_lon = explode(',', $input);
    $lat = $lat_lon[0];
    $lon = $lat_lon[1];

// Lat and lon with comma instead of dot?
// E.g. 52,5145434,13,3501189
} else if($count == 3) {
    $lat_lon = explode(',', $input);
    $lat = $lat_lon[0] . '.' . $lat_lon[1];
    $lon = $lat_lon[2] . '.' . $lat_lon[3];
} else {
    // Invalid input - send the message and exit.
    $msg = '<b>' . getTranslation('invalid_input') . '</b>' . CR . CR;
    $msg .= getTranslation('gym_coordinates_format_error') . CR;
    $msg .= getTranslation('gym_coordinates_format_example');
    sendMessage($update['message']['chat']['id'], $msg);
    exit();
}

// Set gym name.
$gym_name = '#' . $update['message']['from']['id'];

// Get address.
$addr = get_address($lat, $lon);
$address = format_address($addr);

// Insert / update gym.
try {

    global $dbh;

    // Build query to check if gym is already in database or not
    $rs = my_query("
    SELECT    COUNT(*) AS count
    FROM      gyms
      WHERE   gym_name = '{$gym_name}'
     ");

    $row = $rs->fetch();

    // Gym already in database or new
    if (empty($row['count'])) {
        // insert gym in table.
        debug_log('Gym not found in database gym list! Inserting gym "' . $gym_name . '" now.');
        $query = '
        INSERT INTO gyms (gym_name, lat, lon, address, show_gym)
        VALUES (:gym_name, :lat, :lon, :address, 0)
        ';
        $msg = getTranslation('gym_added');
    } else {
        // Get gym by temporary name.
        $gym = get_gym_by_telegram_id($gym_name);

        // If gym is already in the database, make sure no raid is active before continuing!
        if($gym) {
            debug_log('Gym found in the database! Checking for active raid now!');
            $gym_id = $gym['id'];

            // Check for duplicate raid
            $duplicate_id = 0;
            $duplicate_id = active_raid_duplication_check($gym_id);

            // Continue with raid creation
            if($duplicate_id > 0) {
                debug_log('Active raid is in progress!');
                debug_log('Tell user to update the gymname and exit!');

                // Show message that a raid is active on that gym.
                $raid_id = $duplicate_id;
                $raid = get_raid($raid_id);

                // Build message.
                $msg = EMOJI_WARN . SP . getTranslation('raid_already_exists') . SP . EMOJI_WARN . CR . show_raid_poll_small($raid);

                // Tell user to update the gymname first to create another raid by location
                $msg .= getTranslation('gymname_then_location');
                $keys = [];

                // Send message.
                send_message($update['message']['chat']['id'], $msg, ['inline_keyboard' => $keys, 'selective' => true, 'one_time_keyboard' => true]);

                exit();
            } else {
                debug_log('No active raid found! Continuing now ...');
            }
        } else {
            // Set gym_id to 0
            $gym_id = 0;
            debug_log('No gym found in the database! Continuing now ...');
        }

        // Update gyms table to reflect gym changes.
        debug_log('Gym found in database gym list! Updating gym "' . $gym_name . '" now.');
        $query = '
            UPDATE        gyms
            SET           lat = :lat,
                          lon = :lon,
                          address = :address
            WHERE      gym_name = :gym_name
        ';
        $msg = getTranslation('gym_updated');
    }

    $statement = $dbh->prepare($query);
    $statement->execute([
      'gym_name' => $gym_name,
      'lat' => $lat,
      'lon' => $lon,
      'address' => $address
    ]);

    // Get last insert id.
    if (empty($row['count'])) {
        $gym_id = $dbh->lastInsertId();
    }

    // Gym details.
    if($gym_id > 0) {
        $gym = get_gym($gym_id);
        $msg .= CR . CR . get_gym_details($gym);
        $msg .= CR . getTranslation('gym_instructions');
        $msg .= CR . getTranslation('help_gym-edit');
        $msg .= CR . getTranslation('help_gym-name');
        $msg .= CR . getTranslation('help_gym-address');
        $msg .= CR . getTranslation('help_gym-note');
        $msg .= CR . getTranslation('help_gym-delete');
    }
} catch (PDOException $exception) {

    error_log($exception->getMessage());
    $dbh = null;
    exit();
}

// Set keys.
$keys = [];

// Send the message.
send_message($update['message']['chat']['id'], $msg, ['inline_keyboard' => $keys], ['disable_web_page_preview' => 'true']);

?>
