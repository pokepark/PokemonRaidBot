<?php
// Write to log.
debug_log('edit_pokemon()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
bot_access_check($update, 'create');

// Set the id.
$gym_id_plus_letter = $data['id'];

$arg_data = explode(",", $data['arg']);

// Set the raid level and event.
$event_id = $arg_data[0];
$raid_level = $arg_data[1];

// Check if we are creating an event
if($event_id != "N") {
    // If yes, go to date selection
    $action = "edit_time";
}else {
    // If not, select start time
    $action = "edit_starttime";
}

// Get the keys.
$keys = pokemon_keys($gym_id_plus_letter, $raid_level, "edit_starttime", $event_id);

// No keys found.
if (!$keys) {
    $keys = [
        [
            [
                'text'          => getTranslation('abort'),
                'callback_data' => '0:exit:0'
            ]
        ]
    ];
} else {
    if($event_id == "N") {
        $back_id_arg = explode(',', $gym_id_plus_letter);
        $back_id = $back_id_arg[1];
        $back_action = 'edit_raidlevel';
        $back_arg = $back_id_arg[0];
    }else {
        $back_id = $gym_id_plus_letter;
        $back_action = 'edit_event_raidlevel';
        $back_arg = $event_id;
    }

    // Add navigation keys.
    $nav_keys = [];
    $nav_keys[] = universal_inner_key($nav_keys, $back_id, $back_action, $back_arg, getTranslation('back'));
    $nav_keys[] = universal_inner_key($nav_keys, $back_arg, 'exit', '2', getTranslation('abort'));
    $nav_keys = inline_key_array($nav_keys, 2);

    // Merge keys.
    $keys = array_merge($keys, $nav_keys);
}

// Build callback message string.
$callback_response = 'OK';

// Telegram JSON array.
$tg_json = array();

// Answer callback.
$tg_json[] = answerCallbackQuery($update['callback_query']['id'], $callback_response, true);

// Edit the message.
if (isset($update['callback_query']['inline_message_id'])) {
    $tg_json[] = editMessageText($update['callback_query']['inline_message_id'], getTranslation('select_raid_boss') . ':', $keys, NULL, false, true);
} else {
    $tg_json[] = editMessageText($update['callback_query']['message']['message_id'], getTranslation('select_raid_boss') . ':', $keys, $update['callback_query']['message']['chat']['id'], $keys, true);
}

// Telegram multicurl request.
curl_json_multi_request($tg_json);

// Exit.
exit();
