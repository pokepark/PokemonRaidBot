<?php
// Write to log.
debug_log('pokedex_set_shiny()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
$botUser->accessCheck($update, 'pokedex');

// Set the id.
$pokedex_id = $data['id'];

// Get the shiny status.
$arg = $data['arg'];

// Split pokedex_id and form
$dex_id_form = explode('-',$pokedex_id,2);
$dex_id = $dex_id_form[0];
$dex_form = $dex_id_form[1];

// Set shiny or ask to set?
if($data['arg'] == 'setshiny') {
    // Get current shiny status from database.
    require_once(LOGIC_PATH . '/get_pokemon_info.php');
    $pokemon = get_pokemon_info($dex_id, $dex_form);
    
    $shinyText = ($pokemon['shiny'] == 0) ? 'shiny' : 'not_shiny';
    $old_shiny_status = ($pokemon['shiny'] == 0) ? getTranslation('not_shiny') :  EMOJI_SHINY . SP . getTranslation('shiny');
    $newShinyValue = ($pokemon['shiny'] == 0) ? 1 : 0;

    // Back and abort.
    $keys = [
        [
            [
                'text'          => getTranslation($shinyText),
                'callback_data' => $pokedex_id . ':pokedex_set_shiny:' . $newShinyValue
            ]
        ],
        [
            [
                'text'          => getTranslation('back'),
                'callback_data' => $pokedex_id . ':pokedex_edit_pokemon:0'
            ],
            [
                'text'          => getTranslation('abort'),
                'callback_data' => '0:exit:0'
            ]
        ]
    ];

    // Build callback message string.
    $callback_response = getTranslation('select_shiny_status');

    // Set the message.
    $msg = getTranslation('raid_boss') . ': <b>' . get_local_pokemon_name($dex_id, $dex_form) . ' (#' . $dex_id . ')</b>' . CR;
    $msg .= getTranslation('pokedex_current_status') . SP . $old_shiny_status . CR . CR;
    $msg .= '<b>' . getTranslation('pokedex_new_status') . ':</b>';
} else {
    // Update shiny status of pokemon.
    $rs = my_query('
        UPDATE    pokemon
        SET       shiny = ?
        WHERE     pokedex_id = ?
        AND       pokemon_form_id = ?
        ', [$arg, $dex_id, $dex_form]
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
    $msg .= getTranslation('pokedex_new_status') . ':' . CR;
    $msg .= '<b>' . (($arg == 1) ? (getTranslation('shiny')) : (getTranslation('not_shiny'))) . '</b>';
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
