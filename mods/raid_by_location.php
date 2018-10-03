<?php
// Write to log.
debug_log('raid_by_location()');

// For debug.
//debug_log($update);
//debug_log($data);

// Get latitude / longitude values from Telegram
if(isset($update['message']['location'])) {
    $lat = $update['message']['location']['latitude'];
    $lon = $update['message']['location']['longitude'];
} else if(isset($update['callback_query'])) {
    $lat = $data['id'];
    $lon = $data['arg'];
} else {
    sendMessage($update['message']['chat']['id'], '<b>' . getTranslation('not_supported') . '</b>');
    exit();
}

// Debug
debug_log('Lat: ' . $lat);
debug_log('Lon: ' . $lon);

// Build address string.
$address = getTranslation('forest');
if(!empty(GOOGLE_API_KEY)){
    $addr = get_address($lat, $lon);

    // Get full address - Street #, ZIP District
    $address = '';
    $address .= (!empty($addr['street']) ? $addr['street'] : '');
    $address .= (!empty($addr['street_number']) ? ' ' . $addr['street_number'] : '');
    $address .= (!empty($addr) ? ', ' : '');
    $address .= (!empty($addr['postal_code']) ? $addr['postal_code'] . ' ' : '');
    $address .= (!empty($addr['district']) ? $addr['district'] : '');
}

// Temporary gym_name
$gym_name = '#' . $update['message']['chat']['id'];
$gym_letter = substr($gym_name, 0, 1);

$rs = my_query(
    "
    INSERT INTO   gyms
    SET           lat = '{$lat}',
                  lon = '{$lon}',
		  address = '{$db->real_escape_string($address)}',
                  gym_name = '{$db->real_escape_string($gym_name)}'
    "
);

// Get last insert id from db.
$gym_id = my_insert_id();

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
    send_message($update['message']['chat']['id'], $msg, $keys, ['reply_markup' => ['selective' => true, 'one_time_keyboard' => true]]);

// Answer forwarded location message from geo_create.
} else if(isset($update['callback_query'])) {
    // Build callback message string.
    $callback_response = getTranslation('here_we_go');

    // Answer callback.
    answerCallbackQuery($update['callback_query']['id'], $callback_response);

    // Edit the message.
    edit_message($update, getTranslation('select_gym_name'), $keys);
}

// Exit.
exit();
