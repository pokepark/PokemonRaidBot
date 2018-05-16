<?php
// Write to log.
debug_log('pokedex_edit_pokemon()');

// For debug.
//debug_log($update);
//debug_log($data);

// Set the id.
$pokedex_id = $data['id'];

// Init empty keys array.
$keys = array();

// Set the message.
$msg = get_pokemon_info($pokedex_id);
$msg .= '<b>' . getTranslation('pokedex_select_action') . '</b>';

// Create keys array.
$keys = [
    [
        [
            'text'          => getTranslation('pokedex_raid_level'),
            'callback_data' => $pokedex_id . ':pokedex_set_raid_level:setlevel'
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
                'callback_data' => $pokedex_id . ':pokedex_set_cp:min-20-add-0'
            ]
        ],
        [
            [
                'text'          => getTranslation('pokedex_max_cp'),
                'callback_data' => $pokedex_id . ':pokedex_set_cp:max-20-add-0'
            ]
        ],
        [
            [
                'text'          => getTranslation('pokedex_min_weather_cp'),
                'callback_data' => $pokedex_id . ':pokedex_set_cp:min-25-add-0'
            ]
        ],
        [
            [
                'text'          => getTranslation('pokedex_max_weather_cp'),
                'callback_data' => $pokedex_id . ':pokedex_set_cp:max-25-add-0'
            ]
        ],
        [
            [
                'text'          => getTranslation('pokedex_weather'),
                'callback_data' => $pokedex_id . ':pokedex_set_weather:add-0'
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

// Edit message.
edit_message($update, $msg, $keys, false);

// Build callback message string.
$callback_response = 'OK';

// Answer callback.
answerCallbackQuery($update['callback_query']['id'], $callback_response);
