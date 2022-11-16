<?php
// Write to log.
debug_log('pokedex_edit_pokemon()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
$botUser->accessCheck($update, 'pokedex');
require_once(LOGIC_PATH . '/get_pokemon_info.php');
// Set the id.
$poke_id_form = $data['id'];
[$pokedex_id, $pokemon_form_id] = explode('-',$data['id'],2);

// Set the arg.
$arg = $data['arg'];

// Set the message.
$pokemon = get_pokemon_info($pokedex_id, $pokemon_form_id);
$poke_cp = get_formatted_pokemon_cp($pokemon);
$msg = getTranslation('raid_boss') . ': <b>' . get_local_pokemon_name($pokedex_id, $pokemon_form_id) . ' (#' . $pokedex_id . ')</b>' . CR . CR;
$msg .= getTranslation('pokedex_raid_level') . ': ' . getTranslation($pokemon['raid_level'] . 'stars') . CR;
$msg .= (empty($poke_cp)) ? (getTranslation('pokedex_cp') . CR) : $poke_cp . CR;
$msg .= getTranslation('pokedex_weather') . ': ' . get_weather_icons($pokemon['weather']) . CR;
$msg .= (($pokemon['shiny'] == 1) ? (EMOJI_SHINY . SP . getTranslation('shiny')) : (getTranslation('not_shiny'))) . CR . CR;
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
if(!in_array($pokedex_id, $GLOBALS['eggs'])) {
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
        ],
        [
            [
                'text'          => getTranslation('shiny'),
                'callback_data' => $poke_id_form . ':pokedex_set_shiny:setshiny'
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

// Exit.
exit();
