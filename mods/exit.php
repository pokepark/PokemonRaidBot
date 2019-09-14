<?php
// Write to log.
debug_log('exit()');

// For debug.
//debug_log($update);
//debug_log($data);

// Set empty keys.
$keys = [];

// Build message string.
$msg = ($data['arg'] == 1) ? (getTranslation('done') . '!') : (getTranslation('action_aborted'));

// Telegram JSON array.
$tg_json = array();

// Answer callback.
$tg_json[] = answerCallbackQuery($update['callback_query']['id'], $msg, true);

// Edit the message.
$tg_json[] = edit_message($update, $msg, $keys, false, true);

// Telegram multicurl request.
curl_json_multi_request($tg_json);

// Exit.
exit();
