<?php
// Write to log.
debug_log('raid_edit_poke()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check raid access.
raid_access_check($update, $data);

// Set the id.
$raid_id = $data['id'];

// Get current pokemon
$old_pokemon = $data['arg'];

// Get raid level
$raid_level = '0';
$raid_level = get_raid_level($old_pokemon);
debug_log('Raid level of pokemon: ' . $raid_level);

// Level found
if ($raid_level != '0') {
    // Get the keys.
    $keys = pokemon_keys($raid_id, $raid_level, 'raid_set_poke');
} else {
    // Create the keys.
    $keys = [
        [
            [
                'text'          => 'Not supported',
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
$callback_response = getTranslation('select_pokemon');

// Answer callback.
answerCallbackQuery($update['callback_query']['id'], $callback_response);
