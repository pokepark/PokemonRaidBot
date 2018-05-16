<?php
// Write to log.
debug_log('raid_create()');

// For debug.
//debug_log($update);
//debug_log($data);

// Timezone
$tz = TIMEZONE;
$lat = '';
$lon = '';

// Get latitude / longitude values from Telegram Mobile Client
if (isset($update['message']['location'])) {
    $lat = $update['message']['location']['latitude'];
    $lon = $update['message']['location']['longitude'];
}

// Get Userid and chatid from message or set below in case we get latitude and longitude by text message
if (isset($update['message']['from']['id'])) {
    $userid = $update['message']['from']['id'];
}
if (isset($update['message']['chat']['id'])) {
    $chatid = $update['message']['chat']['id'];
}
if (isset($update['message']['chat']['type'])) {
    $chattype = $update['message']['chat']['type'];
}

// Init gym and gym_id
$gym = 0;
$gym_id = 0;

// Get latitude / longitude from message text if empty
// Necessary for Telegram Desktop Client as you cannot send a location :(
if (empty($lat) && empty($lon)) {
    // Set userid, chatid and chattype
    $userid = $update['callback_query']['from']['id'];
    $chatid = $update['callback_query']['message']['chat']['id'];
    $chattype = $update['callback_query']['message']['chat']['type'];

    // Create data array (max. 2)
    $data = explode(',', $data['arg'], 2);

    // Latitude and longitude or Gym ID?
    if($data[0] == "ID") {
        $gym_id = $data[1];
        $gym = get_gym($gym_id);
    } else {
        // Set latitude / longitude
        $lat = $data[0];
        $lon = $data[1];

        // Debug
        debug_log('Lat=' . $lat);
        debug_log('Lon=' . $lon);
    }
}

// Init address and gym name
$fullAddress = "";
$gym_name = "";

// Address and gym name based on input
if($gym_id > 0) {
    // Set name and coordinates.
    $gym_name = $gym['gym_name'];
    $lat = $gym['lat'];
    $lon = $gym['lon'];

    // Get the address.
    $addr = get_address($lat, $lon);

    // Get full address - Street #, ZIP District
    $fullAddress = "";
    $fullAddress .= (!empty($addr['street']) ? $addr['street'] : "");
    $fullAddress .= (!empty($addr['street_number']) ? " " . $addr['street_number'] : "");
    $fullAddress .= (!empty($fullAddress) ? ", " : "");
    $fullAddress .= (!empty($addr['postal_code']) ? $addr['postal_code'] . " " : "");
    $fullAddress .= (!empty($addr['district']) ? $addr['district'] : "");

    // Fallback: Get address from database
    if(empty($fullAddress)) {
	$fullAddress = $gym['address'];
    }
    debug_log('Gym ID: ' . $gym_id);
    debug_log('Gym Name: ' . $gym_name);
    debug_log('Gym Address: ' . $fullAddress);
    debug_log('Lat=' . $lat);
    debug_log('Lon=' . $lon);
} else {
    // Get the address.
    $addr = get_address($lat, $lon);

    // Get full address - Street #, ZIP District
    $fullAddress = "";
    $fullAddress .= (!empty($addr['street']) ? $addr['street'] : "");
    $fullAddress .= (!empty($addr['street_number']) ? " " . $addr['street_number'] : "");
    $fullAddress .= (!empty($fullAddress) ? ", " : "");
    $fullAddress .= (!empty($addr['postal_code']) ? $addr['postal_code'] . " " : "");
    $fullAddress .= (!empty($addr['district']) ? $addr['district'] : "");
}

// Insert new raid or warn about existing raid?
if (!empty($gym_name)) { 
    $raid_id = raid_duplication_check($gym_name,0);
}

// Insert new raid
if ($raid_id != 0) {
    // Check raid ID
    // Positive ID: Raid is completely created
    // Negative ID: Raid is being created at the moment
    $raid_status = (substr($raid_id, 0, 1) == '-') ? 'start' : 'end';

    // Change negative raid ID to positive ID
    $raid_id = ($raid_status == "start") ? (ltrim($raid_id, '-')) : $raid_id;

    // Get the raid data by id.
    $raid = get_raid($raid_id);

    // Create the keys.
    if ($raid_status == "end") {
	$msg = getTranslation('raid_already_exists') . CR . show_raid_poll_small($raid);
        // Init keys.
        $keys = array();

	// Update pokemon and delete raid
        $keys = [
            [
                [
                    'text'          => getTranslation('delete'),
                    'callback_data' => $raid['id'] . ':raids_delete:0'
                ]
            ],
            [
                [
                    'text'          => getTranslation('update_pokemon'),
                    'callback_data' => $raid['id'] . ':raid_edit_poke:' . $raid['pokemon'],
                ]
            ]
        ];

        // Add keys to share.
        $keys_share = share_raid_keys($raid['id'], $userid);
        $keys = array_merge($keys, $keys_share);
    } else {
	// Set message string
	$msg_main = getTranslation('raid_being_created_by_other_user') . CR;
        $msg_main .= getTranslation('gym') . ': ' . $raid['gym_name'] . CR . get_user($raid['user_id']);
	$msg_main .= getTranslation('raid_creation_started_at') . " " . unix2tz($raid['ts_start'], $raid['timezone']) . '.';
	$access_msg_header = '';
	$access_msg_footer = '';

	// Check access to overwrite raid.
	$raid_access = raid_access_check($update, $raid, true);
	if ($raid_access) {
	    // Update user_id and start_time to ensure correct time selection 
            $rs = my_query(
                "
                    UPDATE        raids
                    SET           start_time = NOW()
                       WHERE id = {$raid_id}
                "
            );

	    // Add message header, footer and keys
	    $access_msg_header .= CR . EMOJI_WARN . "<b>" . getTranslation('raid_creation_in_progress') . "</b>" . EMOJI_WARN . CR;
	    $access_msg_header .= CR . "<b>" . getTranslation('raid_creation_in_progress_warning') . "</b>" . CR . CR;
	    $access_msg_footer .= CR . CR . getTranslation('select_raid_level_to_continue') . ':';
	    $keys = raid_edit_start_keys($raid['id']);
            $key_exit = [
                [
                    [
                        'text'          => getTranslation('abort'),
                        'callback_data' => '0:exit:0'
                    ]
                ]
            ];
            $keys = array_merge($keys, $key_exit);
	} else {
            $keys = [];
	}

	// Build message string.
	$msg = $access_msg_header . $msg_main . $access_msg_footer;
    }

    // Edit the message.
    edit_message($update, $msg, $keys);

    // Build callback message string.
    $callback_response = 'OK';

    // Answer callback.
    answerCallbackQuery($update['callback_query']['id'], $callback_response);

    exit();
}

// Address found.
if (!empty($fullAddress)) {
    // Create raid with address.
    $rs = my_query(
        "
        INSERT INTO   raids
        SET           user_id = {$userid},
			          lat = '{$lat}',
			          lon = '{$lon}',
			          first_seen = NOW(),
			          start_time = DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i:00'),
				  gym_name = '{$db->real_escape_string($gym_name)}',
			          timezone = '{$tz}',
			          address = '{$db->real_escape_string($fullAddress)}'
        "
    );

// No address found.
} else {
    // Create raid without address.
    $rs = my_query(
        "
        INSERT INTO   raids
        SET           user_id = {$userid},
			          lat = '{$lat}',
			          lon = '{$lon}',
			          first_seen = NOW(),
			          start_time = DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i:00'),
				  gym_name = '{$db->real_escape_string($gym_name)}',
			          timezone = '{$tz}'
        "
    );
}

// Get last insert id from db.
$id = my_insert_id();

// Write to log.
debug_log('ID=' . $id);

// Get the keys.
$keys = raid_edit_start_keys($id);

// No keys found.
if (!$keys) {
    // Create the keys.
    $keys = [
        [
            [
                'text'          => getTranslation('not_supported'),
                'callback_data' => 'edit:not_supported'
            ]
        ]
    ];
}

// Build message.
$msg = getTranslation('create_raid') . ': <i>' . $fullAddress . '</i>';

// Answer callback or send message based on input prior raid creation
if($gym_id != 0 || (empty($update['message']['location']['latitude']) && empty($update['message']['location']['longitude']))) {
    // Edit the message.
    edit_message($update, $msg . CR . getTranslation('select_raid_level') . ':', $keys);

    // Build callback message string.
    $callback_response = getTranslation('gym_saved');

    // Answer callback.
    answerCallbackQuery($update['callback_query']['id'], $callback_response);
} else {
    // Private chat type.
    if ($chattype == 'private') {
        // Send the message.
        send_message($chatid, $msg . CR . getTranslation('select_raid_level') . ':', $keys);

    } else {
        $reply_to = $update['message']['chat']['id'];
        if ($update['message']['reply_to_message']['message_id']) {
            $reply_to = $update['message']['reply_to_message']['message_id'];
        }

        // Send the message.
        send_message($reply_to, $msg . CR . getTranslation('select_raid_level') . ':', $keys, ['reply_to_message_id' => $reply_to, 'reply_markup' => ['selective' => true, 'one_time_keyboard' => true]]);
    }

    exit();
}

