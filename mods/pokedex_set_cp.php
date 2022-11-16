<?php
// Write to log.
debug_log('pokedex_set_cp()');

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

// Get the type, level and cp
$arg = $data['arg'];
$data = explode("-", $arg);
$cp_type = $data[0];
$cp_level = $data[1];
$cp_value = $data[3];

// Set boosted string
$boosted = ($cp_level == 25) ? '_weather_cp' : '_cp';

// Action to do: Save or add digits to cp
$action = $data[2];

// Get current CP values
require_once(LOGIC_PATH . '/get_pokemon_info.php');
$pokemon = get_pokemon_info($dex_id, $dex_form);
$current_cp = $pokemon[$cp_type . $boosted];

// Log
debug_log('New CP Type: ' . $cp_type);
debug_log('New CP Level: ' . $cp_level);
debug_log('New CP: ' . $cp_value);
debug_log('Old CP: ' . $current_cp);
debug_log('Action: ' . $action);

// Add digits to cp
if($action == 'add') {
    // Init empty keys array.
    $keys = [];

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
    $msg = getTranslation('raid_boss') . ': <b>' . get_local_pokemon_name($dex_id, $dex_form) . ' (#' . $dex_id . ')</b>' . CR;
    $msg .= getTranslation('pokedex_current_cp') . ' ' . $current_cp . CR . CR;
    $msg .= '<b>' .getTranslation('pokedex_' . $cp_type . $boosted) . ': ' . $cp_value . '</b>';

// Save cp to database
} else if($action == 'save') {
    // Set column name.
    $cp_column = $cp_type . $boosted;

    // Update cp of pokemon.
    $rs = my_query('
        UPDATE    pokemon
        SET       ' . $cp_column . ' = ?
        WHERE     pokedex_id = ?
        AND       pokemon_form_id = ?
        ', [$cp_value, $dex_id, $dex_form]
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
    $msg .= getTranslation('pokedex_' . $cp_type . $boosted) . ': <b>' . $cp_value . '</b>';
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
