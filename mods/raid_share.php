<?php
// Write to log.
debug_log('raid_share()');
require_once(LOGIC_PATH . '/send_raid_poll.php');

// For debug.
//debug_log($update);
//debug_log($data);

// Access check.
raid_access_check($update, $data, 'share');

// Get raid id.
$id = $data['id'];

// Get chat id.
$chat = $data['arg'];

$tg_json = send_raid_poll($id, $chat);

// Set callback keys and message
$callback_msg = getTranslation('successfully_shared');
$callback_keys = [];

// Answer callback.
$tg_json[] = answerCallbackQuery($update['callback_query']['id'], $callback_msg, true);

// Edit message.
$tg_json[] = edit_message($update, $callback_msg, $callback_keys, false, true);

// Telegram multicurl request.
curl_json_multi_request($tg_json);

// Exit.
$dbh = null;
exit();
