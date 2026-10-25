<?php
// Write to log.
debug_log('HISTORY');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
$botUser->accessCheck('history');

require_once(LOGIC_PATH .'/history.php');

$msg_keys = create_history_date_msg_keys();
if($msg_keys === false) {
  $msg = getTranslation('history_no_raids_found');
  $keys = [];
}else {
  $msg = $msg_keys[0];
  $keys = $msg_keys[1];
}

send_message(create_chat_object([$update['message']['chat']['id']]), $msg, $keys, ['reply_markup' => ['selective' => true, 'one_time_keyboard' => true], 'disable_web_page_preview' => 'true']);
