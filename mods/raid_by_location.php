<?php
// Write to log.
debug_log('raid_by_location()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
bot_access_check($update, 'create');

// Enabled?
if(!$config->RAID_VIA_LOCATION) {
    debug_log('Creating raids by sharing a location is not enabled in config! Exiting!');
    sendMessage($update['message']['chat']['id'], '<b>' . getTranslation('bot_access_denied') . '</b>');
    exit();
}

// Get latitude / longitude values from Telegram
if(isset($update['message']['location'])) {
    $lat = $update['message']['location']['latitude'];
    $lon = $update['message']['location']['longitude'];
} else if(isset($update['callback_query'])) {
    $lat = $data['id'];
    $lon = $data['arg'];
} else {
    sendMessage($update['message']['chat']['id'], '<b>' . getTranslation('invalid_input') . '</b>');
    exit();
}

// Debug
debug_log('Lat: ' . $lat);
debug_log('Lon: ' . $lon);

// Build address string.
$addr = get_address($lat, $lon);
$address = format_address($addr);

// Temporary gym_name
$gym_name = '#' . $update['message']['chat']['id'];
$gym_letter = substr($gym_name, 0, 1);

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
    } else {
        // Update gyms table to reflect gym changes.
        debug_log('Gym found in database gym list! Updating gym "' . $gym_name . '" now.');
        $query = '
            UPDATE        gyms
            SET           lat = :lat,
                          lon = :lon,
                          address = :address
            WHERE      gym_name = :gym_name
        ';
    }

    $statement = $dbh->prepare($query);
    $statement->execute([
      'gym_name' => $gym_name,
      'lat' => $lat,
      'lon' => $lon,
      'address' => $address
    ]);
    // Get gym id from insert.
    if($gym_id == 0) {
        $gym_id = $dbh->lastInsertId();
    }
} catch (PDOException $exception) {

    error_log($exception->getMessage());
    $dbh = null;
    exit();
}

// Write to log.
debug_log('Gym ID: ' . $gym_id);
debug_log('Gym Name: ' . $gym_name);

// Create the keys.
$keys = [
    [
        [
            'text'          => getTranslation('next'),
            'callback_data' => $gym_letter . ':edit_raidlevel:' . $gym_id
        ]
    ],
    [
        [
            'text'          => getTranslation('abort'),
            'callback_data' => $gym_id . ':exit:2'
        ]
    ]
];

// Answer location message.
if(isset($update['message']['location'])) {
    // Build message.
    $msg = getTranslation('create_raid') . ': <i>' . $address . '</i>';

    // Send message.
    send_message($update['message']['chat']['id'], $msg, ['inline_keyboard' => $keys, 'selective' => true, 'one_time_keyboard' => true]);

// Answer forwarded location message from geo_create.
} else if(isset($update['callback_query'])) {
    // Build callback message string.
    $callback_response = getTranslation('here_we_go');

    // Telegram JSON array.
    $tg_json = array();

    // Answer callback.
    $tg_json[] = answerCallbackQuery($update['callback_query']['id'], $callback_response, true);

    // Edit the message.
    $tg_json[] = edit_message($update, getTranslation('select_gym_name'), $keys, false, true);

    // Telegram multicurl request.
    curl_json_multi_request($tg_json);
}

// Exit.
exit();
