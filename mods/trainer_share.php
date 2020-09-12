<?php
// Write to log.
debug_log('trainer_share()');

// For debug.
//debug_log($update);
//debug_log($data);

// Access check.
bot_access_check($update, 'trainer-share');

// Get chat id.
$chat = $data['arg'];

// Get text and keys.
$text = show_trainerinfo($update);
$keys = keys_trainerinfo();

// Telegram JSON array.
$tg_json = array();

// Send the message.
$tg_json[] = send_message($chat, $text, ['inline_keyboard' => $keys], ['reply_to_message_id' => $chat, 'disable_web_page_preview' => 'true'], true);

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
exit();
