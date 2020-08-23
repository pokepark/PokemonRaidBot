<?php
// Write to log.
debug_log('pokedex_disable_raids()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
bot_access_check($update, 'pokedex');

// Get raid levels.
$id = $data['id'];

// Get argument.
$arg = $data['arg'];

// All raid levels?
if($id == 'X54321') {
    $clear = "'X','5','4','3','2','1'";
} else {
    $clear = "'" . $id . "'";
}

// Specify raid levels.
$levels = array('X', '5', '4', '3', '2', '1');

// Raid level selection
if($arg == 0) {
    // Set message.
    $msg = '<b>' . getTranslation('disable_raid_level') . ':</b>';

    // Init empty keys array.
    $keys = [];

    // All raid level keys.
    $keys[] = array(
        'text'          => getTranslation('pokedex_all_raid_level'),
        'callback_data' => 'X54321:pokedex_disable_raids:1'
    );

    // Add key for each raid level
    foreach($levels as $l) {
        $keys[] = array(
            'text'          => getTranslation($l . 'stars'),
            'callback_data' => $l . ':pokedex_disable_raids:1'
        );
    }

    // Add abort button
    $keys[] = array(
        'text'          => getTranslation('abort'),
        'callback_data' => '0:exit:0'
    );

    // Get the inline key array.
    $keys = inline_key_array($keys, 1);

// Confirmation to disable raid level
} else if($arg == 1) {
    // Get all pokemon with raid levels from database.
    $rs = my_query(
        "
        SELECT    pokedex_id, pokemon_form, raid_level
        FROM      pokemon
        WHERE     raid_level IN ({$clear})
        ORDER BY  raid_level, pokedex_id, pokemon_form != 'normal', pokemon_form
        "
    );

    // Init empty keys array.
    $keys = [];

    // Add key for each raid level
    while ($pokemon = $rs->fetch()) {
        $plevels[$pokemon['pokedex_id'].'-'.$pokemon['pokemon_form']] = $pokemon['raid_level'];
    }

    // Init message and previous.
    $msg = '<b>' . getTranslation('disable_raid_level') . '?</b>' . CR . CR;
    $previous = 'FIRST_RUN';

    // Build the message
    foreach ($plevels as $pid => $lv) {
        // Set current level
        $current = $lv;

        // Add header for each raid level
        if($previous != $current || $previous == 'FIRST_RUN') {
            // Formatting.
            if($previous != 'FIRST_RUN') {
                $msg .= CR;
            }
            // Add header.
            $msg .= '<b>' . getTranslation($lv . 'stars') . ':</b>' . CR ;
        }
        // Get just the dex id without the form.
        $dex_id = explode('-',$pid)[0];

        // Add pokemon with id and name.
        $poke_name = get_local_pokemon_name($dex_id, explode('-',$pid)[1]);
        $msg .= $poke_name . ' (#' . $dex_id . ')' . CR;

        // Prepare next run.
        $previous = $current;
    }

    // Disable.
    $keys[] = array(
        'text'          => getTranslation('yes'),
        'callback_data' => $id . ':pokedex_disable_raids:2'
    );

    // Abort.
    $keys[] = array(
        'text'          => getTranslation('no'),
        'callback_data' => '0:exit:0'
    );

    // Inline keys array.
    $keys = inline_key_array($keys, 2);

// Disable raid level
} else if($arg == 2) {
    debug_log('Disabling old raid bosses for levels: '. $clear);
    disable_raid_level($clear);

    // Message.
    $msg = '<b>' . getTranslation('disabled_raid_level') . ':</b>' . CR;

    // All levels
    if($id == 'X54321') {
        foreach($levels as $l) {
            $msg .= getTranslation($lv . 'stars');
        }

     // Specific level
     } else {
        $msg .= getTranslation($id . 'stars');
     }

     // Empty keys.
     $keys = [];
}

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
