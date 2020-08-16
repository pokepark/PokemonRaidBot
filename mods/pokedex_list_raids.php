<?php
// Write to log.
debug_log('pokedex_list_raids()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
bot_access_check($update, 'pokedex');

// Get all pokemon with raid levels from database.
$rs = my_query(
        "
        SELECT    pokedex_id, pokemon_form_id, raid_level
        FROM      pokemon
        WHERE     raid_level != '0'
        ORDER BY  raid_level, pokedex_id, pokemon_form != 'normal', pokemon_form
        "
    );

// Init empty keys array.
$keys = [];

// Init message and previous.
$msg = '';
$previous = 'FIRST_RUN';

// Build the message
while ($pokemon = $rs->fetch_assoc()) {
    // Set current level
    $current = $pokemon['raid_level'];

    // Add header for each raid level
    if($previous != $current || $previous == 'FIRST_RUN') {
        // Formatting.
        if($previous != 'FIRST_RUN') {
            $msg .= CR;
        }
        // Add header.
        $msg .= '<b>' . getTranslation($pokemon['raid_level'] . 'stars') . ':</b>' . CR ;
    }
    // Add pokemon with id and name.
    $poke_name = get_local_pokemon_name($pokemon['pokedex_id'], $pokemon['pokemon_form_id']);
    $msg .= $poke_name . ' (#' . $pokemon['pokedex_id'] . ')' . CR;

    // Add button to edit pokemon.
    $keys[] = array(
        'text'          => '[' . $pokemon['raid_level'] . ']' . SP . $poke_name,
        'callback_data' => $pokemon['pokedex_id'] . '-' . $pokemon['pokemon_form_id'] . ':pokedex_edit_pokemon:0'
    );

    // Prepare next run.
    $previous = $current;
}

if(!empty($msg)) {
    // Set the message.
    $msg .= CR . '<b>' . getTranslation('pokedex_edit_pokemon') . '</b>';
    // Set the keys.
    $keys = inline_key_array($keys, 2);

    // Done key.
    $keys[] = [
        [
            'text'          => getTranslation('done'),
            'callback_data' => '0:exit:1'
        ]
    ];
} else {
    // Set empty keys.
    $keys = [];

    // Set the message.
    $msg = getTranslation('pokedex_not_found');
}

// Build callback message string.
$callback_response = getTranslation('select_pokemon');

// Telegram JSON array.
$tg_json = array();

// Answer callback.
$tg_json[] = answerCallbackQuery($update['callback_query']['id'], $callback_response, true);

// Edit message.
$tg_json[] = edit_message($update, $msg, $keys, false, true);

// Telegram multicurl request.
curl_json_multi_request($tg_json);

// Exit.
exit();
