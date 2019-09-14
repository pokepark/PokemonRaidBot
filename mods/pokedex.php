<?php
// Write to log.
debug_log('pokedex()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
bot_access_check($update, 'pokedex');

// Get the limit.
$limit = $data['id'];

// Get the action.
$action = $data['arg'];

if ($update['callback_query']['message']['chat']['type'] == 'private') {
    // Set message.
    $msg = getTranslation('pokedex_list_of_all') . CR . CR . '<b>' . getTranslation('pokedex_edit_pokemon') . '</b>';

    // Get pokemon.
    $keys = edit_pokedex_keys($limit, $action);

    // Empty keys?
    if (!$keys) {
	$msg = getTranslation('pokedex_not_found');
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
} 

// Exit.
exit();
