<?php
// Write to log.
debug_log('pokedex_list_raids()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
bot_access_check($update, 'pokedex');

// Get all pokemon with raid levels from database.
$rs = my_query(
        "
            SELECT    raid_bosses.pokedex_id, raid_bosses.pokemon_form_id, raid_bosses.raid_level, raid_bosses.date_start, raid_bosses.date_end
            FROM      raid_bosses
            LEFT JOIN pokemon
            ON        raid_bosses.pokedex_id = pokemon.pokedex_id
            AND       raid_bosses.pokemon_form_id = pokemon.pokemon_form_id
            ORDER BY  raid_bosses.date_start, raid_bosses.date_end, raid_bosses.raid_level, raid_bosses.pokedex_id, pokemon.pokemon_form_name != 'normal', pokemon.pokemon_form_name, raid_bosses.pokemon_form_id
        "
    );

// Init empty keys array.
$keys = [];

// Init message and previous.
$msg = '';
$previous_level = 'FIRST_RUN';
$previous_date_start = 'FIRST_RUN';
$previous_date_end = '';
// Build the message
while ($pokemon = $rs->fetch()) {
    // Set current level
    $current_level = $pokemon['raid_level'];
    $current_date_start = $pokemon['date_start'];
    $current_date_end = $pokemon['date_end'];

    if($previous_date_start != $current_date_start || $previous_date_end != $current_date_end || $previous_date_start == 'FIRST_RUN') {
        // Formatting.
        if($previous_date_start != 'FIRST_RUN') {
            $msg .= CR;
        }
        // Add header.
        $msg .= '<b>' . $pokemon['date_start'] . ' - ' . $pokemon['date_end'] . ':</b>' . CR ;
        $previous_level = 'FIRST_RUN';
    }
    // Add header for each raid level
    if($previous_level != $current_level || $previous_level == 'FIRST_RUN') {
        // Formatting.
        if($previous_level != 'FIRST_RUN') {
            $msg .= CR;
        }
        // Add header.
        $msg .= '<b>' . getTranslation($pokemon['raid_level'] . 'stars') . ':</b>' . CR ;
    }
    // Add pokemon with id and name.
    $poke_name = get_local_pokemon_name($pokemon['pokedex_id'], $pokemon['pokemon_form_id']);
    $msg .= $poke_name . ' (#' . $pokemon['pokedex_id'] . ')' . CR;

    // Add button to edit pokemon.
    if($pokemon['date_start'] == '1970-01-01 00:00:01' && $pokemon['date_end'] == '2038-01-19 03:14:07') {
        $keys[] = array(
            'text'          => '[' . $pokemon['raid_level'] . ']' . SP . $poke_name,
            'callback_data' => $pokemon['pokedex_id'] . '-' . $pokemon['pokemon_form_id'] . ':pokedex_edit_pokemon:0'
        );
    }

    // Prepare next run.
    $previous_level = $current_level;
    $previous_date_start = $current_date_start;
    $previous_date_end = $current_date_end;
}

if(!empty($msg)) {
    // Set the message.
    $msg .= CR . '<b>' . getTranslation('pokedex_edit_pokemon') . '</b>';
    // Set the keys.
    $keys = inline_key_array($keys, 2);

    // Done key.
    $keys[] = [
        [
            'text'          => getTranslation('done'),
            'callback_data' => '0:exit:1'
        ]
    ];
} else {
    // Set empty keys.
    $keys = [];

    // Set the message.
    $msg = getTranslation('pokedex_not_found');
}

// Build callback message string.
$callback_response = getTranslation('select_pokemon');

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
