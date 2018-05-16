<?php
// Write to log.
debug_log('pokedex_list_raids()');

// For debug.
//debug_log($update);
//debug_log($data);

// Get all pokemon with raid levels from database.
$rs = my_query(
        "
        SELECT    pokedex_id, raid_level
        FROM      pokemon
        WHERE     raid_level != '0'
        ORDER BY  raid_level, pokedex_id
        "
    );

// Init empty keys array.
$keys = array();

// Add key for each raid level
while ($pokemon = $rs->fetch_assoc()) {
    $levels[$pokemon['pokedex_id']] = $pokemon['raid_level'];
}

// Init message and previous.
$msg = '';
$previous = 'FIRST_RUN';

// Build the message
foreach ($levels as $id => $lv) {
    // Set current level
    $current = $lv;

    // Add header for each raid level
    if($previous != $current || $previous == 'FIRST_RUN') {
        // Formatting.
        if($previous != 'FIRST_RUN') {
            $msg .= CR;
        }
        // Add header.
        $msg .= '<b>' . getTranslation($lv . 'stars') . ':</b>' . CR ;
    }
    // Add pokemon with id and name.
    $poke_name = get_local_pokemon_name($id);
    $msg .= $poke_name . ' (#' . $id . ')' . CR;

    // Add button to edit pokemon.
    $keys[] = array(
        'text'          => $poke_name,
        'callback_data' => $id . ':pokedex_edit_pokemon:0'
    );

    // Prepare next run.
    $previous = $current;
}

if(!empty($msg)) {
    // Set the message.
    $msg .= CR . '<b>' . getTranslation('pokedex_edit_pokemon') . '</b>';
    // Set the keys.
    $keys = inline_key_array($keys, 3);

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

// Edit message.
edit_message($update, $msg, $keys, false);

// Build callback message string.
$callback_response = getTranslation('select_pokemon');

// Answer callback.
answerCallbackQuery($update['callback_query']['id'], $callback_response);
