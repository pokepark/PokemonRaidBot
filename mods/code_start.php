<?php
// Write to log.
debug_log('code_start');

// For debug.
//debug_log($update);
//debug_log($data);

// Allow anyone to use /code
// Check access.
//bot_access_check($update, 'list');

// Get raid
$raid = get_raid_with_pokemon($code_raid_id);

// Init text and keys.
$text = '';
$keys = [];

// Get current UTC time and raid end UTC time.
$now = utcnow();
$end_time = $raid['end_time'];

// Raid ended already.
if ($end_time > $now) {
    // Set text and keys.
    $gym_name = $raid['gym_name'];
    if(empty($gym_name)) {
        $gym_name = '';
    }

    $text .= $gym_name . CR;
    $raid_day = dt2date($raid['start_time']);
    $now = utcnow();
    $today = dt2date($now);
    $start = dt2time($raid['start_time']);
    $end = dt2time($raid['end_time']);
    $text .= get_local_pokemon_name($raid['pokemon'], $raid['pokemon_form']) . SP . '-' . SP . (($raid_day == $today) ? '' : ($raid_day . ', ')) . $start . SP . getTranslation('to') . SP . $end . CR . CR;

    // Add exit key.
    $keys = [
        [
            [
                'text'          => getTranslation('start_raid_public'),
                'callback_data' => $raid['id'] . ':code:public-unconfirmed'
            ],
            [
                'text'          => getTranslation('start_raid_private'),
                'callback_data' => $raid['id'] . ':code:0-0-0-add'
            ]
        ],
        [
            [
                'text'          => getTranslation('abort'),
                'callback_data' => '0:exit:0'
            ]
        ]
    ];

    // Build message.
    $msg = '<b>' . getTranslation('start_raid_now') . ':</b>' . CR . CR;
    $msg .= $text;
} else {
    $msg = '<b>' . getTranslation('group_code_share') . ':</b>' . CR;
    $msg .= '<b>' . getTranslation('no_active_raids_found') . '</b>';
}

// Send message.
send_message($update['message']['chat']['id'], $msg, ['inline_keyboard' => $keys, 'selective' => true, 'one_time_keyboard' => true]);

?>
