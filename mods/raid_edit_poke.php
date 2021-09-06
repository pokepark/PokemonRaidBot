<?php
// Write to log.
debug_log('raid_edit_poke()');

// For debug.
//debug_log($update);
//debug_log($data);

// Access check.
raid_access_check($update, $data, 'pokemon');

// Set the id.
$raid_id = $data['id'];

// Get raid level
$raid_level = $data['arg'];

debug_log('Raid level of pokemon: ' . $raid_level);

// Level found
if ($raid_level != '0') {
    // Get the keys.
    $keys = pokemon_keys($raid_id, $raid_level, 'raid_set_poke');

    // Add navigation keys.
    $nav_keys = [];
    $nav_keys[] = universal_inner_key($nav_keys, '0', 'exit', '0', getTranslation('abort'));
    $nav_keys = inline_key_array($nav_keys, 1);

    // Merge keys.
    $keys = array_merge($keys, $nav_keys);
} else {
    // Create the keys.
    $keys = [
        [
            [
                'text'          => getTranslation('not_supported'),
                'callback_data' => '0:exit:0'
            ]
        ]
    ];
}

// Build callback message string.
$callback_response = getTranslation('select_pokemon');

// Telegram JSON array.
$tg_json = array();

// Answer callback.
$tg_json[] = answerCallbackQuery($update['callback_query']['id'], $callback_response, true);

$raid = get_raid($raid_id);
// Set the message.
$msg = getTranslation('raid_boss') . ':' . SP . get_local_pokemon_name($raid['pokemon'],$raid['pokemon_form']) . CR . CR;
$msg .= '<b>' . getTranslation('select_raid_boss') . ':</b>';
if (isset($update['callback_query']['inline_message_id'])) {
    $tg_json[] = editMessageText($update['callback_query']['inline_message_id'], $msg, $keys, NULL, false, true);
} else {
    $tg_json[] = editMessageText($update['callback_query']['message']['message_id'], $msg, $keys, $update['callback_query']['message']['chat']['id'], $keys, true);
}

// Telegram multicurl request.
curl_json_multi_request($tg_json);

// Exit.
exit();
