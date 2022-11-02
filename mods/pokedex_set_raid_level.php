<?php
// Write to log.
debug_log('pokedex_set_raid_level()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
$botUser->accessCheck($update, 'pokedex');

// Set the id.
$pokedex_id = $data['id'];

// Get the raid level.
$arg = $data['arg'];

// Split pokedex_id and form
$dex_id_form = explode('-',$pokedex_id,2);
$dex_id = $dex_id_form[0];
$dex_form = $dex_id_form[1];

// Set raid level or show raid levels?
if($data['arg'] == "setlevel") {
    $raid_levels = str_split('0' . RAID_LEVEL_ALL);

    // Init empty keys array.
    $keys = [];

    // Create keys array.
    foreach($raid_levels as $lv) {
        $keys[] = [
            array(
                'text'          => getTranslation($lv . 'stars'),
                'callback_data' => $pokedex_id . ':pokedex_set_raid_level:' . $lv
            )
        ];
    }

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
    $callback_response = getTranslation('select_raid_level');

    // Set the message.
    $msg = getTranslation('raid_boss') . ': <b>' . get_local_pokemon_name($dex_id, $dex_form) . ' (#' . $dex_id . ')</b>' . CR;
    $old_raid_level = get_raid_level($dex_id, $dex_form);
    $msg .= getTranslation('pokedex_current_raid_level') . ' ' . getTranslation($old_raid_level . 'stars') . CR . CR;
    $msg .= '<b>' . getTranslation('pokedex_new_raid_level') . ':</b>';
} else {
    // Update raid level of pokemon.
    if($arg == 0 && get_raid_level($dex_id, $dex_form) != 0) {
        $rs = my_query(
                "
                DELETE FROM raid_bosses
                WHERE     pokedex_id = '{$dex_id}'
                AND       pokemon_form_id = '{$dex_form}'
                AND       scheduled = 0
                "
            );
    }else {
        $rs = my_query(
                "
                INSERT INTO raid_bosses (pokedex_id, pokemon_form_id, raid_level)
                VALUES ('{$dex_id}', '{$dex_form}', '{$arg}')
                "
            );
    }

    // Init empty keys array.
    $keys = [];

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
    $msg .= getTranslation('pokedex_new_raid_level') . ':' . CR;
    $msg .= '<b>' . getTranslation($arg . 'stars') . '</b>';
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
