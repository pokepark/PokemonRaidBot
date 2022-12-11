<?php
// Write to log.
debug_log('pokedex()');
require_once(LOGIC_PATH . '/edit_pokedex_keys.php');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
$botUser->accessCheck('pokedex');

// Get the limit.
$limit = $data['l'] ?? 0;

// Set message.
$msg = getTranslation('pokedex_list_of_all') . CR . CR . '<b>' . getTranslation('pokedex_edit_pokemon') . '</b>';

// Get pokemon.
$keys = edit_pokedex_keys($limit);

// Empty keys?
if (!$keys) {
  $msg = getTranslation('pokedex_not_found');
}

// Telegram JSON array.
$tg_json = array();

// Answer callback.
$tg_json[] = answerCallbackQuery($update['callback_query']['id'], 'OK', true);

// Edit message.
$tg_json[] = edit_message($update, $msg, $keys, false, true);

// Telegram multicurl request.
curl_json_multi_request($tg_json);
