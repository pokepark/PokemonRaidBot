<?php
// Write to log.
debug_log('HISTORY');
require_once(LOGIC_PATH . '/show_raid_poll.php');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
$botUser->accessCheck('history');

$raid_id = $data['r'];

$raid = get_raid($raid_id);

$config->RAID_POLL_HIDE_USERS_TIME = 0;
$msg = show_raid_poll($raid)['full'];

$tg_json = [];

// Answer callback.
$tg_json[] = answerCallbackQuery($update['callback_query']['id'], 'OK', true);

$backData = $data;
$backData[0] = 'history_raids';
$keys[] = [
  button(getTranslation('back'), $backData),
  button(getTranslation('done'), ['exit', 'd' => '1'])
];

// Edit message.
$tg_json[] = edit_message($update, $msg, $keys, ['disable_web_page_preview'=>true], true);

// Telegram multicurl request.
curl_json_multi_request($tg_json);
