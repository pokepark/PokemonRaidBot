<?php
// Write to log.
debug_log('POKEDEX()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
bot_access_check($update, 'pokedex');

// Get pokemon name or dex id.
$pokemon = trim(substr($update['message']['text'], 8));

// Init empty keys array.
$keys = [];

// Check if we recived pokemon name or dex id.
if(!empty($pokemon)) {
    // Pokedex_id received?
    if(is_numeric($pokemon)) {
        $pokedex_id = $pokemon;
    // Pokemon name received?
    } else {
        $pokemon_name_form = get_pokemon_id_by_name($pokemon);
        $pokedex_id = explode("-", $pokemon_name_form)[0];
    }
    $statement = $dbh->prepare("SELECT pokedex_id, pokemon_form_id FROM pokemon WHERE pokedex_id = :pokedex_id");
    $statement->execute([":pokedex_id" => $pokedex_id]);
    while ($pokemon = $statement->fetch()) {
        $keys[] = [
            [
                'text'          => get_local_pokemon_name($pokemon['pokedex_id'], $pokemon['pokemon_form_id']),
                'callback_data' => $pokemon['pokedex_id'] . '-' . $pokemon['pokemon_form_id'] . ':pokedex_edit_pokemon:0'
            ]
        ];
    }
    // Set message.
    $msg = '<b>' . getTranslation('pokedex_edit_pokemon') . '</b>';
}

if(count($keys) == 0 ) {
    $query = my_query("SELECT * FROM pokemon WHERE pokedex_id='9995'"); // A simple check to see if pokemon table has all the necessary data in it
    if($query->rowCount() > 0) {
        // Create keys array.
        $keys = [
            [
                [
                    'text'          => getTranslation('pokedex_raid_pokemon'),
                    'callback_data' => '0:pokedex_list_raids:0'
                ]
            ],
            [
                [
                    'text'          => getTranslation('edit_pokemon'),
                    'callback_data' => '0:pokedex:0'
                ]
            ],
            [
                [
                    'text'          => getTranslation('disable_raid_level'),
                    'callback_data' => '0:pokedex_disable_raids:0'
                ]
            ],
            [
                [
                    'text'          => getTranslation('import'),
                    'callback_data' => '0:pokedex_import:0'
                ]
                ]
        ];
    }
    $keys[][] = [
                'text'          => getTranslation('update_pokemon_table'),
                'callback_data' => '0:getdb:0'
            ];
    // Set message.
    $msg = '<b>' . getTranslation('pokedex_start') . ':</b>';
}
$keys[] = [
    [
        'text'          => getTranslation('abort'),
        'callback_data' => '0:exit:0'
    ]
];

// Send message.
send_message($update['message']['chat']['id'], $msg, $keys, ['reply_markup' => ['selective' => true, 'one_time_keyboard' => true]]);

?>
