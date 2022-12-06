<?php
// Write to log.
debug_log('edit_event_raidlevel()');
require_once(LOGIC_PATH . '/get_gym.php');
require_once(LOGIC_PATH . '/raid_edit_raidlevel_keys.php');

// For debug.
//debug_log($update);
//debug_log($data);

// Get gym data via ID
$gym = get_gym($data['g']);

// Get event ID
$event_id = $data['e'];

// Telegram JSON array.
$tg_json = array();

//Initialize admin rights table [ ex-raid , raid-event ]
$admin_access = [false, false];
// Check access - user must be admin for raid_level X
$admin_access[0] = $botUser->accessCheck('ex-raids', true);
// Check access - user must be admin for raid event creation
$admin_access[1] = $botUser->accessCheck('event-raids', true);

// Get the keys.
$keys = raid_edit_raidlevel_keys($data, $admin_access, $event_id);

$backData = $data;
$backData[0] = 'edit_event';
// Add navigation keys.
$keys[] = [
  [
    'text' => getTranslation('back'),
    'callback_data' => formatCallbackData($backData)
  ],
  [
    'text' => getTranslation('abort'),
    'callback_data' => 'exit'
  ]
];

// Get event info
$q = my_query('SELECT name, description FROM events WHERE id = ? LIMIT 1', [$event_id]);
$rs = $q->fetch();

// Build message.
if($event_id == 'X') {
  $msg = '<b>' . getTranslation('Xstars') . '</b>' . CR;
}else {
  $msg = '<b>' . $rs['name'] . '</b>' . CR . $rs['description'] . CR;
}
$msg .= getTranslation('create_raid') . ': <i>' . (($gym['address']=='') ? $gym['gym_name'] : $gym['address']) . '</i>';

// Build callback message string.
$callback_response = getTranslation('gym_saved');

// Answer callback.
$tg_json[] = answerCallbackQuery($update['callback_query']['id'], $callback_response, true);

// Edit the message.
$tg_json[] = edit_message($update, $msg . CR . getTranslation('select_raid_level') . ':', $keys, false, true);

// Telegram multicurl request.
curl_json_multi_request($tg_json);
