<?php
// Write to log.
debug_log('TRAINER()');
require_once(LOGIC_PATH . '/get_user.php');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
$botUser->accessCheck('trainer');

$gymarea = $data['i'] ?? false;

if($gymarea !== false) {
  my_query('UPDATE users SET gymarea = ? WHERE user_id = ?',[$gymarea, $botUser->userId]);
}else {
  $q = my_query('SELECT gymarea FROM users WHERE user_id = ? LIMIT 1', [$botUser->userId]);
  $gymarea = $q->fetch()['gymarea'];
}

// Init empty keys array.
$keys = [];

$json = json_decode(file_get_contents(CONFIG_PATH . '/geoconfig_gym_areas.json'), 1);
$gymareaName = '';
foreach($json as $area) {
  if($area['id'] == $gymarea) $gymareaName = $area['name'];
  $keys[] = [
    'text'          => $area['name'],
    'callback_data' => formatCallbackData(['trainerGymarea', 'i' => $area['id']])
  ];
}
$keys = inline_key_array($keys, 2);
$keys[] = [
  [
    'text'          => getTranslation('back'),
    'callback_data' => formatCallbackData(['trainer', 'arg' => 0])
  ],
  [
    'text'          => getTranslation('done'),
    'callback_data' => formatCallbackData(['exit', 'arg' => 1])
  ]
];
// Set message.
$msg = '<b>' . getTranslation('trainerinfo_set_yours') . '</b>';

$msg .= CR . CR . get_user($botUser->userId, true);
$msg .= '<b>' . getTranslation('default_gymarea') . ': </b>';
$msg .= $gymareaName;

// Answer callback.
answerCallbackQuery($update['callback_query']['id'], 'OK');

// Edit message.
edit_message($update, $msg, $keys, false);
