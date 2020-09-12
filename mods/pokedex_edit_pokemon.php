<?php
// Write to log.
debug_log('pokedex_edit_pokemon()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
bot_access_check($update, 'pokedex');

// Set the id.
$poke_id_form = $data['id'];
$dex_id_form = explode('-',$data['id']);
$pokedex_id = $dex_id_form[0];
$pokemon_form = $dex_id_form[1];

// Set the arg.
$arg = $data['arg'];

// Init empty keys array.
$keys = [];

// Set the message.
$msg = get_pokemon_info($pokedex_id, $pokemon_form);
$msg .= '<b>' . getTranslation('pokedex_select_action') . '</b>';

// Create keys array.
$keys = [
    [
        [
            'text'          => getTranslation('pokedex_raid_level'),
            'callback_data' => $poke_id_form . ':pokedex_set_raid_level:setlevel'
        ]
    ]
];

// Raid-Egg? Hide specific options!
$eggs = $GLOBALS['eggs'];
if(!in_array($pokedex_id, $eggs)) {
    $keys_cp_weather = [
        [
            [
                'text'          => getTranslation('pokedex_min_cp'),
                'callback_data' => $poke_id_form . ':pokedex_set_cp:min-20-add-0'
            ]
        ],
        [
            [
                'text'          => getTranslation('pokedex_max_cp'),
                'callback_data' => $poke_id_form . ':pokedex_set_cp:max-20-add-0'
            ]
        ],
        [
            [
                'text'          => getTranslation('pokedex_min_weather_cp'),
                'callback_data' => $poke_id_form . ':pokedex_set_cp:min-25-add-0'
            ]
        ],
        [
            [
                'text'          => getTranslation('pokedex_max_weather_cp'),
                'callback_data' => $poke_id_form . ':pokedex_set_cp:max-25-add-0'
            ]
        ],
        [
            [
                'text'          => getTranslation('pokedex_weather'),
                'callback_data' => $poke_id_form . ':pokedex_set_weather:add-0'
            ]
        ]
    ];

    $keys = array_merge($keys, $keys_cp_weather);
}

// Back and abort.
$keys[] = [
    [
        'text'          => getTranslation('back'),
        'callback_data' => '0:pokedex:0'
    ],
    [
        'text'          => getTranslation('abort'),
        'callback_data' => '0:exit:0'
    ]
];

// Send message.
if($arg == 'id-or-name') {
    // Send message.
    send_message($update['message']['chat']['id'], $msg, ['inline_keyboard' => $keys, 'selective' => true, 'one_time_keyboard' => true]);

// Edit message.
} else {
    // Build callback message string.
    $callback_response = 'OK';

    // Telegram JSON array.
    $tg_json = array();

    // Answer callback.
    $tg_json[] = answerCallbackQuery($update['callback_query']['id'], $callback_response, true);

    // Edit message.
    $tg_json[] = edit_message($update, $msg, $keys, false, true);

    // Telegram multicurl request.
    curl_json_multi_request($tg_json);
}

// Exit.
exit();
