<?php
require_once(LOGIC_PATH . '/pokemon_keys.php');
// Write to log.
debug_log('raid_edit_poke()');

// For debug.
//debug_log($update);
//debug_log($data);

// Set the id.
$raid_id = $data['r'] ?? 0;

// Access check.
$botUser->raidaccessCheck($raid_id, 'pokemon');

// Get raid level
$raid_level = $data['rl'];

debug_log('Raid level of pokemon: ' . $raid_level);

// Level found
if ($raid_level != '0') {
  // Get the keys.
  $keys = pokemon_keys($data, $raid_level, 'raid_set_poke');

  $keys[][] = [
    'text' => getTranslation('abort'),
    'callback_data' => 'exit'
  ];
} else {
  // Create the keys.
  $keys = [
    [
      [
        'text'          => getTranslation('not_supported'),
        'callback_data' => 'exit'
      ]
    ]
  ];
}

// Build callback message string.
$callback_response = getTranslation('select_pokemon');

// Telegram JSON array.
$tg_json = array();

// Answer callback.
$tg_json[] = answerCallbackQuery($update['callback_query']['id'], $callback_response, true);

$raid = get_raid($raid_id);
// Set the message.
$msg = getTranslation('raid_boss') . ':' . SP . get_local_pokemon_name($raid['pokemon'],$raid['pokemon_form']) . CR . CR;
$msg .= '<b>' . getTranslation('select_raid_boss') . ':</b>';
if (isset($update['callback_query']['inline_message_id'])) {
  $tg_json[] = editMessageText($update['callback_query']['inline_message_id'], $msg, $keys, NULL, false, true);
} else {
  $tg_json[] = editMessageText($update['callback_query']['message']['message_id'], $msg, $keys, $update['callback_query']['message']['chat']['id'], $keys, true);
}

// Telegram multicurl request.
curl_json_multi_request($tg_json);
