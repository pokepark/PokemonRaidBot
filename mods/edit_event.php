<?php
// Write to log.
debug_log('edit_event()');
require_once(LOGIC_PATH . '/keys_event.php');

// For debug.
//debug_log($update);
//debug_log($data);

//Initialize admin rights table [ ex-raid , raid-event ]
$admin_access = [false, false];
// Check access - user must be admin for raid_level X
$admin_access[0] = $botUser->accessCheck('ex-raids', true);
// Check access - user must be admin for raid event creation
$admin_access[1] = $botUser->accessCheck('event-raids', true);

// Get the keys.
$keys = keys_event($data, 'edit_event_raidlevel', $admin_access);

$backData = $data;
$backData[0] = 'edit_raidlevel';

// Add navigation keys.
$keys[] = [
  button(getTranslation('back'), $backData),
  button(getTranslation('abort'), 'exit')
];


// Build callback message string.
$callback_response = 'OK';

// Telegram JSON array.
$tg_json = array();

// Answer callback.
$tg_json[] = answerCallbackQuery($update['callback_query']['id'], $callback_response, true);

// Edit the message.
$tg_json[] = editMessageText($update['callback_query']['message']['message_id'], getTranslation('select_raid_boss') . ':', $keys, $update['callback_query']['message']['chat']['id'], false, true);

// Telegram multicurl request.
curl_json_multi_request($tg_json);
