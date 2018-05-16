<?php
// Write to log.
debug_log('pokedex_set_weather()');

// For debug.
//debug_log($update);
//debug_log($data);

// Set the id.
$pokedex_id = $data['id'];

// Get the action, old and new weather
$arg = $data['arg'];
$data = explode("-", $arg);
$action = $data[0];
$new_weather = $data[1];
$old_weather = get_pokemon_weather($pokedex_id);

// Log
debug_log('Action: ' . $action);
debug_log('Old weather: ' . $old_weather);
debug_log('New weather: ' . $new_weather);

// Add weather
if($action == 'add') {
    // Init empty keys array.
    $keys = array();

    // Get the keys.
    $keys = weather_keys($pokedex_id, 'pokedex_set_weather', $arg);

    // Build callback message string.
    $callback_response = 'OK';

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

    // Set the message.
    $msg = getTranslation('raid_boss') . ': <b>' . get_local_pokemon_name($pokedex_id) . ' (#' . $pokedex_id . ')</b>' . CR;
    $msg .= getTranslation('pokedex_current_weather') . get_weather_icons($old_weather) . CR . CR;
    $msg .= '<b>' . getTranslation('pokedex_new_weather') . get_weather_icons($new_weather) . '</b>';

// Save weather to database
} else if($action == 'save') {
    // Update weather of pokemon.
    $rs = my_query(
            "
            UPDATE    pokemon
            SET       weather = {$new_weather}
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
    $msg .= getTranslation('pokedex_weather') . ':' . CR;
    $msg .= '<b>' . get_weather_icons($new_weather) . '</b>';
}


// Edit message.
edit_message($update, $msg, $keys, false);

// Answer callback.
answerCallbackQuery($update['callback_query']['id'], $callback_response);

exit();
