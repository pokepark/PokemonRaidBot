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

// Set the raid level.
$raid_level = $data['arg'];

// Get the keys.
$keys = pokemon_keys($gym_id_plus_letter, $raid_level, "edit_starttime");

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
    // Back key id, action and arg
    $back_id_arg = explode(',', $gym_id_plus_letter);
    $back_id = $back_id_arg[1];
    $back_action = 'edit_raidlevel';
    $back_arg = $back_id_arg[0];

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
