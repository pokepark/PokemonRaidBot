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

// Check access.
bot_access_check($update, 'create');

// Get data from message text. (remove: "/raid ")
$gym_data = trim(substr($update['message']['text'], 5));

// Create data array (max. 9)
$data = explode(',', $gym_data, 5);

/**
 * Info:
 * [0] = Boss pokedex id (optional: including form, e.g. 487-origin)
 * [1] = raid duration in minutes
 * [2] = gym name
 * [3] = gym team
 * [4] = optional: raid countdown minutes
 */

// Invalid data received.
if (count($data) < 4) {
    send_message($update['message']['chat']['id'], 'Invalid input - Parameter mismatch', []);
    exit;
}

// Raid boss name
$boss = $data[0];
if (empty($boss) || strpos($boss, '.') !== false ) {
    send_message($update['message']['chat']['id'], 'Invalid input - Raidboss ID is empty or invalid', []);
    exit;
}
if (strpos($boss, '-') === false ) {
    // Add normal form as default if form is missing
    $boss = $boss . '-normal';
}

// Endtime from input
$endtime = $data[1];

// Team
$team = $data[3];

// Escape comma in Raidname
$gym_name = str_replace('|',',',$data[2]);

// Get countdown minutes when specified, otherwise 0 minutes until raid starts
$countdown = 0;
if (!empty($data[4])) {
    $countdown = $data[4];
}

$gym_id = 0;
try {
    // Update gym name in raid table.
    $query = '
        SELECT id
        FROM gyms
        WHERE
            gym_name LIKE :gym_name
        LIMIT 1
    ';
    $statement = $dbh->prepare( $query );
    $statement->execute(['gym_name' => $gym_name]);
    while ($row = $statement->fetch()) {
        $gym_id = $row['id'];
    }
}
catch (PDOException $exception) {

    error_log($exception->getMessage());
    $dbh = null;
    exit;
}

/* Remove all unknown gyms */
if ($gym_id <= 0) {
   //send_message($update['message']['chat']['id'], 'Invalid input - Gym is not matched in database', []);
   exit;
}

$start = date("Y-m-d H:i:s",strtotime(date("Y-m-d H:i:s")." +".$countdown." minutes"));
$end = date("Y-m-d H:i:s",strtotime(date("Y-m-d H:i:s")." +".$endtime." minutes"));

// Insert new raid or update existing raid/ex-raid?
$raid_id = raid_duplication_check($gym_id,$start, $end);

if ($raid_id > 0) {

    // Get current pokemon from database for raid.
    $rs_ex_raid = my_query(
        "
        SELECT    pokemon, pokemon_form
            FROM      raids
              WHERE   id = {$raid_id}
        "
    );

    // Get row.
    $row_ex_raid = $rs_ex_raid->fetch();
    $poke_name = $row_ex_raid['pokemon'].'-'.$row_ex_raid['pokemon_form'];
    debug_log('Comparing the current pokemon to pokemons from ex-raid list now...');
    debug_log('Current Pokemon in database for this raid: ' . $poke_name);

    // Make sure it's not an Ex-Raid before updating the pokemon.
    $raid_level = get_raid_level($row_ex_raid['pokemon'], $row_ex_raid['pokemon_form']);
    if($raid_level == 'X') {
        // Ex-Raid! Update only team in raids table.
        debug_log('Current pokemon is an ex-raid pokemon: ' . $poke_name);
        debug_log('Pokemon "' .$poke_name . '" will NOT be updated to "' . $boss . '"!');
        $stmt = $dbh->prepare(
            "
            UPDATE    raids
            SET	      gym_team = :team
              WHERE   id = :raid_id
            "
        );
        $stmt->execute([
          'team' => $team,
          'raid_id' => $raid_id
        ]);
    } else {
        // Update pokemon and team in raids table.
        debug_log('Current pokemon is NOT an ex-raid pokemon: ' . $poke_name);
        debug_log('Pokemon "' .$poke_name . '" will be updated to "' . $boss . '"!');
        $stmt = $dbh->prepare(
            "
            UPDATE    raids
            SET       pokemon = :boss
		      gym_team = :team
            WHERE     id = :raid_id
            "
        );
        $stmt->execute([
          'boss' => $boss,
          'team' => $team,
          'raid_id' => $raid_id
        ]);
    }

    // Debug log
    debug_log('Updated raid ID: ' . $raid_id);
    //send_message($update['message']['chat']['id'], 'Raid is updated: ' . $raid_id, []);

    // Get raid data.
    $raid = get_raid($raid_id);

    // Exit now after update of raid and message.
    exit();
}

// Build the query.
$stmt = $dbh->prepare(
    "
    INSERT INTO   raids
    SET           pokemon = :boss,
		  user_id = :user_id,
		  first_seen = DATE_FORMAT(UTC_TIMESTAMP(), '%Y-%m-%d %H:%i:00'),
		  start_time = DATE_ADD(first_seen, INTERVAL {$countdown} MINUTE),
		  end_time = DATE_ADD(start_time, INTERVAL {$endtime} MINUTE),
		  gym_team = :team,
		  gym_id = :gym_id
    "
);
$stmt->execute([
  'boss' => $boss,
  'user_id' => $update['message']['from']['id'],
  'team' => $team,
  'gym_id' => $gym_id
]);

// Get last insert id from db.
$id = $dbh->lastInsertId();

// Write to log.
debug_log('ID=' . $id);

// Get raid data.
$raid = get_raid($id);

// Send location.
if ($config->RAID_LOCATION) {
    //$loc = send_location($update['message']['chat']['id'], $raid['lat'], $raid['lon']);
    $msg_text = !empty($raid['address']) ? $raid['address'] . ', ' . substr(strtoupper($config->BOT_ID), 0, 1) . '-ID = ' . $raid['id'] : $raid['pokemon'] . ', ' . $raid['id']; // DO NOT REMOVE " ID = " --> NEEDED FOR $config->CLEANUP PREPARATION!
    $loc = send_venue($update['message']['chat']['id'], $raid['lat'], $raid['lon'], "", $msg_text);

    // Write to log.
    debug_log('location:');
    debug_log($loc);
}

// Set text.
$text = show_raid_poll($raid);

// Raid picture
if($config->RAID_PICTURE) {
  require_once(LOGIC_PATH . '/raid_picture.php');
  $picture_url = raid_picture_url($raid);
}


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
    $chat = $update['message']['chat']['id'];

    // Send the message.
    if($config->RAID_PICTURE) {
        send_photo($chat, $picture_url, $text['short'], $keys, ['disable_web_page_preview' => 'true']);
    } else {
        send_message($chat, $text['full'], ['inline_keyboard' => $keys], ['disable_web_page_preview' => 'true']);
    }


} else {
    // Set reply to.
    $reply_to = $update['message']['chat']['id'];

    // Set keys.
    $keys = keys_vote($raid);

    if ($update['message']['reply_to_message']['message_id']) {
        $reply_to = $update['message']['reply_to_message']['message_id'];
    }

    // Send the message.
    $chat = $update['message']['chat']['id'];
    if($config->RAID_PICTURE) {
        send_photo($chat, $picture_url, $text['short'], $keys, ['reply_to_message_id' => $reply_to, 'reply_markup' => ['selective' => true, 'one_time_keyboard' => true], 'disable_web_page_preview' => 'true']);
    } else {
        send_message($chat, $text['full'], ['inline_keyboard' => $keys, 'selective' => true, 'one_time_keyboard' => true], ['disable_web_page_preview' => 'true']);
    }
}

?>
