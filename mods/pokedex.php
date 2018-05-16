<?php
// Write to log.
debug_log('pokedex()');

// For debug.
//debug_log($update);
//debug_log($data);

// Get the limit.
$limit = $data['id'];

// Get the action.
$action = $data['arg'];

if ($update['callback_query']['message']['chat']['type'] == 'private') {
    // Set message.
    $msg = getTranslation('pokedex_list_of_all') . CR . CR . '<b>' . getTranslation('pokedex_edit_pokemon') . '</b>';

    // Get pokemon.
    $all_pokemon = ($action == 1) ? true : false;
    $keys = edit_pokedex_keys($limit, $action, $all_pokemon);

    // Empty keys?
    if (!$keys) {
	$msg = getTranslation('pokedex_not_found');
    }

    // Edit message.
    edit_message($update, $msg, $keys, false);

    // Build callback message string.
    $callback_response = 'OK';

    // Answer callback.
    answerCallbackQuery($update['callback_query']['id'], $callback_response);
} 

exit();
