<?php
// Write to log.
debug_log('trainer_share()');
require_once(LOGIC_PATH . '/keys_trainerinfo.php');
require_once(LOGIC_PATH . '/show_trainerinfo.php');

// For debug.
//debug_log($update);
//debug_log($data);

// Access check.
$botUser->accessCheck($update, 'trainer-share');

// Get chat id.
$chat = $data['arg'];

// Get text and keys.
$text = show_trainerinfo($update);
$keys = keys_trainerinfo();

// Telegram JSON array.
$tg_json = array();

// Send the message.
$tg_json[] = send_message($chat, $text, $keys, ['disable_web_page_preview' => 'true'], true, 'trainer');

// Set callback keys and message
$callback_msg = getTranslation('successfully_shared');
$callback_keys = [];

// Answer callback.
$tg_json[] = answerCallbackQuery($update['callback_query']['id'], $callback_msg, true);

// Edit message.
$tg_json[] = edit_message($update, $callback_msg, $callback_keys, false, true);

// Telegram multicurl request.
curl_json_multi_request($tg_json);
