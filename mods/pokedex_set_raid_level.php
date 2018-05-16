<?php
// Write to log.
debug_log('pokedex_set_raid_level()');

// For debug.
//debug_log($update);
//debug_log($data);

// Set the id.
$pokedex_id = $data['id'];

// Get the raid level.
$arg = $data['arg'];

// Set raid level or show raid levels?
if($data['arg'] == "setlevel") {
    // Get raid levels from database.
    $rs = my_query(
            "
            SHOW COLUMNS
            FROM      pokemon
            WHERE     field = 'raid_level'
            "
        );

    //Get type information
    while ($enum = $rs->fetch_assoc()) {
        // Type should be something like this:                    enum('0','1','2','3','4','5','X')
        $type = $enum['Type'];
    }
    
    // Remove   enum('   from string, $raid_levels will now be:   0','1','2','3','4','5','X')
    $raid_levels = str_replace("enum('", "", $type);

    // Remove   ')   from string, $raid_levels will now be:       0','1','2','3','4','5','X
    $raid_levels = str_replace("')", "", $raid_levels);

    // Explode by   ','   so the resulting array will now be:     0 1 2 3 4 5 X
    $raid_levels = explode("','", $raid_levels);

    // Init empty keys array.
    $keys = array();

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
    $msg = getTranslation('raid_boss') . ': <b>' . get_local_pokemon_name($pokedex_id) . ' (#' . $pokedex_id . ')</b>' . CR;
    $old_raid_level = get_raid_level($pokedex_id);
    $msg .= getTranslation('pokedex_current_raid_level') . ' ' . getTranslation($old_raid_level . 'stars') . CR . CR;
    $msg .= '<b>' . getTranslation('pokedex_new_raid_level') . ':</b>';
} else {
    // Update raid level of pokemon.
    $rs = my_query(
            "
            UPDATE    pokemon
            SET       raid_level = '{$arg}'
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
    $msg .= getTranslation('pokedex_new_raid_level') . ':' . CR;
    $msg .= '<b>' . getTranslation($arg . 'stars') . '</b>';
}


// Edit message.
edit_message($update, $msg, $keys, false);

// Answer callback.
answerCallbackQuery($update['callback_query']['id'], $callback_response);
