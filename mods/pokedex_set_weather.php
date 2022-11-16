<?php
// Write to log.
debug_log('pokedex_set_weather()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
$botUser->accessCheck($update, 'pokedex');

// Set the id.
$pokedex_id = $data['id'];

// Split pokedex_id and form
$dex_id_form = explode('-',$pokedex_id,2);
$dex_id = $dex_id_form[0];
$dex_form = $dex_id_form[1];

// Get the action, old and new weather
$arg = $data['arg'];
$data = explode("-", $arg);
$action = $data[0];
$new_weather = $data[1];

require_once(LOGIC_PATH . '/get_pokemon_info.php');
$pokemon = get_pokemon_info($dex_id, $dex_form);
$old_weather = $pokemon['weather'];

// Log
debug_log('Action: ' . $action);
debug_log('Old weather: ' . $old_weather);
debug_log('New weather: ' . $new_weather);

// Add weather
if($action == 'add') {
    // Init empty keys array.
    $keys = [];

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
    $msg = getTranslation('raid_boss') . ': <b>' . get_local_pokemon_name($dex_id, $dex_form) . ' (#' . $dex_id . ')</b>' . CR;
    $msg .= getTranslation('pokedex_current_weather') . ' ' . get_weather_icons($old_weather) . CR . CR;
    $msg .= '<b>' . getTranslation('pokedex_new_weather') . '</b> ' . get_weather_icons($new_weather);

// Save weather to database
} else if($action == 'save') {
    // Update weather of pokemon.
    $rs = my_query(
            '
            UPDATE    pokemon
            SET       weather = ?
            WHERE     pokedex_id = ?
            AND       pokemon_form_id = ?
            ', [$new_weather, $dex_id, $dex_form]
        );

    // Back to pokemon and done keys.
    $keys = [
        [
            [
                'text'          => getTranslation('back') . ' (' . get_local_pokemon_name($dex_id, $dex_form) . ')',
                'callback_data' => $pokedex_id . ':pokedex_edit_pokemon:0'
            ],
            [
                'text'          => getTranslation('done'),
                'callback_data' => '0:exit:1'
            ]
        ]
    ];

    // Build callback message string.
    $callback_response = getTranslation('pokemon_saved') . ' ' . get_local_pokemon_name($dex_id, $dex_form);

    // Set the message.
    $msg = getTranslation('pokemon_saved') . CR;
    $msg .= '<b>' . get_local_pokemon_name($dex_id, $dex_form) . ' (#' . $dex_id . ')</b>' . CR . CR;
    $msg .= getTranslation('pokedex_weather') . ':' . CR;
    $msg .= '<b>' . get_weather_icons($new_weather) . '</b>';
}

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
