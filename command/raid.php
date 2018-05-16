<?php
// Write to log.
debug_log('RAID()');

// For debug.
//debug_log($update);
//debug_log($data);

/**
 * Mimic inline message to create raid poll from external notifier.
 *
 */
$tz = TIMEZONE;

// Get data from message text. (remove: "/raid ")
$gym_data = trim(substr($update['message']['text'], 5));

// Create data array (max. 9)
$data = explode(',', $gym_data, 9);

/**
 * Info:
 * [0] = Boss pokedex id
 * [1] = latitude
 * [2] = longitude
 * [3] = raid duration in minutes
 * [4] = gym team
 * [5] = gym name
 * [6] = district (or street)
 * [7] = street (or district)
 * [8] = optional: raid countdown minutes
 */

// Invalid data received.
if (count($data) < 8) {
    send_message($update['message']['chat']['id'], 'Invalid input - Paramter mismatch', []);
    exit;
}

// Raid boss name
$boss = $data[0];
if (empty($boss) || !is_numeric($boss) || strpos($boss, '.') !== false ) {
    send_message($update['message']['chat']['id'], 'Invalid input - Raidboss ID is empty or invalid', []);
    exit;
}

// Get latitude / longitude from data.
$lat = floatval($data[1]);
$lon = floatval($data[2]);

// Format lat/long values.
$lat = substr($lat, 0, strpos('.', $lat) + 9);
$lon = substr($lon, 0, strpos('.', $lon) + 9);

// Endtime from input
$endtime = $data[3];

// Team
$team = $data[4];

// Escape comma in Raidname
$name = str_replace('|',',',$data[5]);

// Build address string.
if(!empty(GOOGLE_API_KEY)){
    $addr = get_address($lat, $lon);

    // Get full address - Street #, ZIP District
    $address = "";
    $address .= (!empty($addr['street']) ? $addr['street'] : "");
    $address .= (!empty($addr['street_number']) ? " " . $addr['street_number'] : "");
    $address .= (!empty($addr) ? ", " : "");
    $address .= (!empty($addr['postal_code']) ? $addr['postal_code'] . " " : "");
    $address .= (!empty($addr['district']) ? $addr['district'] : "");
} else {
    //Based on input order of [6] and [7] it'll be either: Street, District or District, Street
    $address = (!empty($data[6]) ? $data[6] : '') . (!empty($data[7]) ? ", " . $data[7] : "");
}

// Get countdown minutes when specified, otherwise 0 minutes until raid starts
$countdown = 0;
if (!empty($data[8])) {
    $countdown = $data[8];
}

// Insert new raid or update existing raid/ex-raid?
$raid_id = raid_duplication_check($name,($endtime + $countdown));

if ($raid_id > 0) {
    // Get current pokemon from database for raid.
    $rs_ex_raid = my_query(
        "
        SELECT    pokemon
            FROM      raids
              WHERE   id = {$raid_id}
        "
    );

    // Get row.
    $row_ex_raid = $rs_ex_raid->fetch_assoc();
    $poke_name = $row_ex_raid['pokemon'];
    debug_log('Comparing the current pokemon to pokemons from ex-raid list now...');
    debug_log('Current Pokemon in database for this raid: ' . $poke_name);

    // Make sure it's not an Ex-Raid before updating the pokemon.
    $raid_level = get_raid_level($poke_name);
    if($raid_level == 'X') {
        // Ex-Raid! Update only team in raids table.
        debug_log('Current pokemon is an ex-raid pokemon: ' . $poke_name);
        debug_log('Pokemon "' .$poke_name . '" will NOT be updated to "' . $boss . '"!');
        my_query(
            "
            UPDATE    raids
            SET	      gym_team = '{$db->real_escape_string($team)}'
              WHERE   id = {$raid_id}
            "
        );
    } else {
        // Update pokemon and team in raids table.
        debug_log('Current pokemon is NOT an ex-raid pokemon: ' . $poke_name);
        debug_log('Pokemon "' .$poke_name . '" will be updated to "' . $boss . '"!');
        my_query(
            "
            UPDATE    raids
            SET       pokemon = '{$db->real_escape_string($boss)}',
		      gym_team = '{$db->real_escape_string($team)}'
              WHERE   id = {$raid_id}
            "
        );
    }

    // Debug log
    debug_log('Updated raid ID: ' . $raid_id);

    // Get raid data.
    $raid = get_raid($raid_id);

    //Debug
    // Set text.
    //$text = '<b>Raid aktualisiert!  R-ID = ' . $raid_id . "</b>" . CR;
    //$text .= CR . show_raid_poll($raid);

    // Send the message
    //sendMessage($update['message']['chat']['id'], $text);

    // Exit now after update of raid and message.
    exit();
}

// Address found.
if (!empty($address)) {
    // Insert gym with address, lat and lon to database if not already in database
    $gym2db = insert_gym($name, $lat, $lon, $address);

    // Build the query.
    $rs = my_query(
        "
        INSERT INTO   raids
        SET           pokemon = '{$db->real_escape_string($boss)}',
		              user_id = {$update['message']['from']['id']},
		              lat = '{$lat}',
		              lon = '{$lon}',
		              first_seen = DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i:00'),
		              start_time = DATE_ADD(first_seen, INTERVAL {$countdown} MINUTE),
		              end_time = DATE_ADD(start_time, INTERVAL {$endtime} MINUTE),
		              gym_team = '{$db->real_escape_string($team)}',
		              gym_name = '{$db->real_escape_string($name)}',
		              timezone = '{$tz}',
		              address = '{$db->real_escape_string($address)}'
        "
    );
// No address found.
} else {
    // Build the query.
    $rs = my_query(
        "
        INSERT INTO   raids
        SET           pokemon = '{$db->real_escape_string($boss)}',
		              user_id = {$update['message']['from']['id']},
		              lat = '{$lat}',
		              lon = '{$lon}',
		              first_seen = DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i:00'),
		              start_time = DATE_ADD(first_seen, INTERVAL {$countdown} MINUTE),
		              end_time = DATE_ADD(start_time, INTERVAL {$endtime} MINUTE),
		              gym_team = '{$db->real_escape_string($team)}',
		              gym_name = '{$db->real_escape_string($name)}',
		              timezone = '{$tz}'
        "
    );
}

// Get last insert id from db.
$id = my_insert_id();

// Write to log.
debug_log('R-ID=' . $id);

// Get raid data.
$raid = get_raid($id);

// Send location.
if (RAID_LOCATION == true) {
    //$loc = send_location($update['message']['chat']['id'], $raid['lat'], $raid['lon']);
    $msg_text = !empty($raid['address']) ? $raid['address'] . ', R-ID = ' . $raid['id'] : $raid['pokemon'] . ', ' . $raid['id']); // DO NOT REMOVE " R-ID = " --> NEEDED FOR CLEANUP PREPARATION!
    $loc = send_venue($update['message']['chat']['id'], $raid['lat'], $raid['lon'], "", $msg_text);

    // Write to log.
    debug_log('location:');
    debug_log($loc);
}

// Set text.
$text = show_raid_poll($raid);

// Private chat type.
if ($update['message']['chat']['type'] == 'private' || $update['callback_query']['message']['chat']['type'] == 'private') {
    // Set keys.
    $keys = [
        [
            [
                'text'                => getTranslation('share'),
                'switch_inline_query' => strval($id),
            ]
        ]
    ];

    // Send the message.
    send_message($update['message']['chat']['id'], $text, $keys, ['disable_web_page_preview' => 'true']);

} else {
    // Set reply to.
    $reply_to = $update['message']['chat']['id'];

    // Set keys.
    $keys = keys_vote($raid);

    if ($update['message']['reply_to_message']['message_id']) {
        $reply_to = $update['message']['reply_to_message']['message_id'];
    }

    // Send the message.
    send_message($update['message']['chat']['id'], $text, $keys, ['reply_to_message_id' => $reply_to, 'reply_markup' => ['selective' => true, 'one_time_keyboard' => true], 'disable_web_page_preview' => 'true']);
}

exit();
