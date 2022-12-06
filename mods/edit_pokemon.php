<?php
require_once(LOGIC_PATH . '/pokemon_keys.php');
// Write to log.
debug_log('edit_pokemon()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
$botUser->accessCheck('create');

// Set the raid level and event.
$eventId = $data['e'] ?? NULL;
$raidLevel = $data['rl'];

// Check if we are creating an event
if($eventId != NULL) {
  // If yes, go to date selection
  $action = "edit_time";
}else {
  // If not, select start time
  $action = "edit_starttime";
}

// Get the keys.
$keys = pokemon_keys($data, $raidLevel, 'edit_starttime', $eventId);

$back_action = ($eventId == NULL) ? 'edit_raidlevel' : 'edit_event_raidlevel';

// Add navigation keys.
$backData = $data;
$backData['callbackAction'] = $back_action;
$keys[] = [
  [
    'text' => getTranslation('back'),
    'callback_data' => formatCallbackData($backData),
  ],
  [
    'text' => getTranslation('abort'),
    'callback_data' => formatCallbackData(['callbackAction' => 'exit'])
  ]
];

// Build callback message string.
$callback_response = 'OK';

// Telegram JSON array.
$tg_json = array();

// Answer callback.
$tg_json[] = answerCallbackQuery($update['callback_query']['id'], $callback_response, true);

// Edit the message.
if (isset($update['callback_query']['inline_message_id'])) {
  $tg_json[] = editMessageText($update['callback_query']['inline_message_id'], getTranslation('select_raid_boss') . ':', $keys, NULL, false, true);
} else {
  $tg_json[] = editMessageText($update['callback_query']['message']['message_id'], getTranslation('select_raid_boss') . ':', $keys, $update['callback_query']['message']['chat']['id'], $keys, true);
}

// Telegram multicurl request.
curl_json_multi_request($tg_json);
