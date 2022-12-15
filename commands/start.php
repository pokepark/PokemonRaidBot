<?php
// Write to log.
debug_log('START()');
require_once(LOGIC_PATH . '/gymMenu.php');
require_once(LOGIC_PATH . '/raid_get_gyms_list_keys.php');

// For debug.
//debug_log($update);
//debug_log($data);

$new_user = new_user($update['message']['from']['id']);
$access = $botUser->accessCheck('create', true, $new_user);
if(!$access && !$new_user) {
  if($botUser->accessCheck('list', true)){
    debug_log('No access to create, will do a list instead');
    require('list.php');
  }else {
    $response_msg = '<b>' . getTranslation('bot_access_denied') . '</b>';
    send_message($update['message']['from']['id'], $response_msg);
  }
  exit;
}
if($new_user && !$access) {
  // Tutorial
  require_once('tutorial.php');
  exit;
}
// Trim away everything before "/start "
$searchterm = $update['message']['text'];
$searchterm = substr($searchterm, 7);
debug_log($searchterm, 'SEARCHTERM');

// Start raid message.
if(strpos($searchterm , 'c0de-') === 0) {
  $code_raid_id = explode("-", $searchterm, 2)[1];
  require_once(ROOT_PATH . '/mods/code_start.php');
  exit();
}

// Get the keys by gym name search.
$addAbortKey = true;
$keys = false;
if(!empty($searchterm)) {
  $keys = raid_get_gyms_list_keys($searchterm);
  $msg = getTranslation('select_gym_name');
}

// Get the keys if nothing was returned.
if(!$keys) {
  $gymarea = resolveDefaultGymarea($botUser->userId);
  $keys_and_gymarea = gymMenu('create', false, 1, false, $gymarea);
  $keys = $keys_and_gymarea['keys'];
  $msg = $keys_and_gymarea['gymareaTitle'];
  $addAbortKey = false;
}

// No keys found.
if ($addAbortKey) {
  $keys[][] = button(getTranslation('abort'), 'exit');
}

// Send message.
send_message($update['message']['chat']['id'], $msg, $keys, ['reply_markup' => ['selective' => true, 'one_time_keyboard' => true]]);
