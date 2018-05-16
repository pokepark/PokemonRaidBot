<?php
// Write to log.
debug_log('pokedex_set_cp()');

// For debug.
//debug_log($update);
//debug_log($data);

// Set the id.
$pokedex_id = $data['id'];

// Get the type, level and cp
$arg = $data['arg'];
$data = explode("-", $arg);
$cp_type = $data[0];
$cp_level = $data[1];
$cp_value = $data[3];

// Set boosted string
if($cp_level == 25) {
    $boosted = '_weather_cp';
} else {
    $boosted = '_cp';
}

// Action to do: Save or add digits to cp
$action = $data[2];

// Get current CP values
$cp_old = get_pokemon_cp($pokedex_id);
$current_cp = $cp_old[$cp_type . $boosted];

// Log
debug_log('New CP Type: ' . $cp_type);
debug_log('New CP Level: ' . $cp_level);
debug_log('New CP: ' . $cp_value);
debug_log('Old CP: ' . $current_cp);
debug_log('Action: ' . $action);

// Add digits to cp
if($action == 'add') {
    // Init empty keys array.
    $keys = array();

    // Get the keys.
    $keys = cp_keys($pokedex_id, 'pokedex_set_cp', $arg);

    // Back and abort.
    $keys[] = [
        [
            'text'          => getTranslation('back'),
            'callback_data' => $pokedex_id . ':pokedex_edit_pokemon:0'
        ],
        [
            'text'          => getTranslation('abort'),
            'callback_data' => '0:exit:0'
        ]
    ];

    // Build callback message string.
    $callback_response = 'OK';

    // Set the message.
    $msg = getTranslation('raid_boss') . ': <b>' . get_local_pokemon_name($pokedex_id) . ' (#' . $pokedex_id . ')</b>' . CR;
    $msg .= getTranslation('pokedex_current_cp') . ' ' . $current_cp . CR . CR;
    $msg .= '<b>' .getTranslation('pokedex_' . $cp_type . $boosted) . ': ' . $cp_value . '</b>';

// Save cp to database
} else if($action == 'save') {
    // Set IV level for database
    if($cp_level == 25) {
        $weather = 'weather_cp';
    } else {
        $weather = 'cp';
    }

    // Set column name.
    $cp_column = $cp_type . '_' . $weather;

    // Update cp of pokemon.
    $rs = my_query(
            "
            UPDATE    pokemon
            SET       $cp_column = {$cp_value}
            WHERE     pokedex_id = {$pokedex_id}
            "
        );

    // Init empty keys array.
    $keys = array();

    // Back to pokemon and done keys.
    $keys = [
        [
            [
                'text'          => getTranslation('back') . ' (' . get_local_pokemon_name($pokedex_id) . ')',
                'callback_data' => $pokedex_id . ':pokedex_edit_pokemon:0'
            ],
            [
                'text'          => getTranslation('done'),
                'callback_data' => '0:exit:1'
            ]
        ]
    ];

    // Build callback message string.
    $callback_response = getTranslation('pokemon_saved') . ' ' . get_local_pokemon_name($pokedex_id);

    // Set the message.
    $msg = getTranslation('pokemon_saved') . CR;
    $msg .= '<b>' . get_local_pokemon_name($pokedex_id) . ' (#' . $pokedex_id . ')</b>' . CR . CR;
    $msg .= getTranslation('pokedex_' . $cp_type . $boosted) . ': <b>' . $cp_value . '</b>';
}

// Edit message.
edit_message($update, $msg, $keys, false);

// Answer callback.
answerCallbackQuery($update['callback_query']['id'], $callback_response);

exit();
