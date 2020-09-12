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

// Check if we recived pokemon name or dex id.
if(!empty($pokemon)) {
    $forward = false;
    // Pokedex_id received?
    if(is_numeric($pokemon)) {
        // Always normal form
        $pokemon = $pokemon . '-0';
        // Set forward to true
        $forward = true;

    // Pokemon name received?
    } else {
        // Get pokemon id by name.
        $pokemon = get_pokemon_id_by_name($pokemon);

        // Set forward to true
        if($pokemon != 0) {
            $forward = true;
        }
    }

    if($forward == true) {
        // Reset data array
        $data = [];
        $data['id'] = $pokemon;
        $data['action'] = 'pokedex_edit_pokemon';
        $data['arg'] = 'id-or-name';

        // Write to log.
        debug_log($data, '* NEW DATA= ');

        // Edit pokemon and exit.
        include_once(ROOT_PATH . '/mods/pokedex_edit_pokemon.php');
        exit();
    }
}

// Init empty keys array.
$keys = [];

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
            'text'          => getTranslation('import') . SP . '(Pokebattler)',
            'callback_data' => '0:pokebattler:0'
        ]
    ],
    [
        [
            'text'          => getTranslation('abort'),
            'callback_data' => '0:exit:0'
        ]
    ]
];

// Set message.
$msg = '<b>' . getTranslation('pokedex_start') . ':</b>';

// Send message.
send_message($update['message']['chat']['id'], $msg, ['inline_keyboard' => $keys, 'selective' => true, 'one_time_keyboard' => true]);

?>
