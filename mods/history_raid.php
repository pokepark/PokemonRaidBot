<?php
// Write to log.
debug_log('HISTORY');
require_once(LOGIC_PATH . '/show_raid_poll.php');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
$botUser->accessCheck($update, 'history');

// Expected callback data: [Date, YYYY-MM-DD]/[GYM_LETTER]:history_raid:[GYM_ID]/[RAID_ID]

$arg_data = explode('/',$data['arg']);
$gym_id = $arg_data[0];
$raid_id = $arg_data[1];

$raid = get_raid($raid_id);

$config->RAID_POLL_HIDE_USERS_TIME = 0;
$msg = show_raid_poll($raid)['full'];

$tg_json = [];

// Answer callback.
$tg_json[] = answerCallbackQuery($update['callback_query']['id'], 'OK', true);

$keys[] = [
  [
    'text'          => getTranslation('back'),
    'callback_data' => $data['id'] . ':history_raids:' . $gym_id
  ],
  [
    'text'          => getTranslation('done'),
    'callback_data' => '0:exit:1'
  ],
];

// Edit message.
$tg_json[] = edit_message($update, $msg, $keys, ['disable_web_page_preview'=>true], true);

// Telegram multicurl request.
curl_json_multi_request($tg_json);
