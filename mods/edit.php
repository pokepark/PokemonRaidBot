<?php
// Write to log.
debug_log('edit()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check raid access.
raid_access_check($update, $data);

// Set the id.
$raid_id = $data['id'];

// Set the raid level.
$raid_level = $data['arg'];

// Get the keys.
$keys = pokemon_keys($raid_id, $raid_level, "edit_poke");

// No keys found.
if (!$keys) {
    $keys = [
        [
            [
                'text'          => getTranslation('not_supported'),
                'callback_data' => 'edit:not_supported'
            ]
        ]
    ];
}

if (isset($update['callback_query']['inline_message_id'])) {
    editMessageText($update['callback_query']['inline_message_id'], getTranslation('select_raid_boss') . ':', $keys);
} else {
    editMessageText($update['callback_query']['message']['message_id'], getTranslation('select_raid_boss') . ':', $keys, $update['callback_query']['message']['chat']['id'], $keys);
}

// Build callback message string.
$callback_response = 'Ok';

// Answer callback.
answerCallbackQuery($update['callback_query']['id'], $callback_response);
