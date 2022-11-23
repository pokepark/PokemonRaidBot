<?php
// Write to log.
debug_log('edit_raidlevel()');
require_once(LOGIC_PATH . '/active_raid_duplication_check.php');
require_once(LOGIC_PATH . '/get_gym.php');
require_once(LOGIC_PATH . '/raid_edit_raidlevel_keys.php');
require_once(LOGIC_PATH . '/show_raid_poll_small.php');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
$botUser->accessCheck('create');

// Get gym data via ID in arg
$gym_id = $data['g'];
$gym = get_gym($gym_id);

$gym_first_letter = $data['fl'] ?? '';
$showHidden = $data['h'] ?? 0;
$gymareaId = $data['ga'] ?? false;

// Telegram JSON array.
$tg_json = array();

// Active raid?
$duplicate_id = active_raid_duplication_check($gym_id);
if ($duplicate_id > 0) {
  $keys = [];
  $raid_id = $duplicate_id;
  $raid = get_raid($raid_id);
  $msg = EMOJI_WARN . SP . getTranslation('raid_already_exists') . SP . EMOJI_WARN . CR . show_raid_poll_small($raid);

  $keys = share_keys($raid_id, 'raid_share', $update, $raid['level']);

  // Add keys for sharing the raid.
  if(!empty($keys)) {
    // Exit key
    $keys = universal_key($keys, '0', 'exit', '0', getTranslation('abort'));
  }

  // Answer callback.
  $tg_json[] = answerCallbackQuery($update['callback_query']['id'], getTranslation('raid_already_exists'), true);

  // Edit the message.
  $tg_json[] = edit_message($update, $msg, $keys, false, true);

  // Telegram multicurl request.
  curl_json_multi_request($tg_json);

  // Exit.
  exit();
}

//Initialize admin rights table [ ex-raid , raid-event ]
$admin_access = [false,false];
// Check access - user must be admin for raid_level X
$admin_access[0] = $botUser->accessCheck('ex-raids', true);
// Check access - user must be admin for raid event creation
$admin_access[1] = $botUser->accessCheck('event-raids', true);

// Get the keys.
$keys = raid_edit_raidlevel_keys($gym_id, $gym_first_letter, $admin_access);

// Add navigation keys.
$nav_keys = [];
$nav_keys[] = [
  'text'          => getTranslation('back'),
  'callback_data' => formatCallbackData(['callbackAction' => 'gymMenu', 'stage' => 2, 'a' => 'create', 'h' => $showHidden, 'ga' => $gymareaId, 'fl' => $gym_first_letter])
];
$nav_keys[] = universal_inner_key($nav_keys, $gym_id, 'exit', '2', getTranslation('abort'));
$nav_keys = inline_key_array($nav_keys, 2);
// Merge keys.
$keys = array_merge($keys, $nav_keys);

// Build message.
$msg = getTranslation('create_raid') . ': <i>' . (($gym['address']=="") ? $gym['gym_name'] : $gym['address']) . '</i>';

// Build callback message string.
$callback_response = getTranslation('gym_saved');

// Answer callback.
$tg_json[] = answerCallbackQuery($update['callback_query']['id'], $callback_response, true);

// Edit the message.
$tg_json[] = edit_message($update, $msg . CR . getTranslation('select_raid_level') . ':', $keys, false, true);

// Telegram multicurl request.
curl_json_multi_request($tg_json);
