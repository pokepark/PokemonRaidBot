<?php
// Write to log.
debug_log('START()');
require_once(LOGIC_PATH . '/gymMenu.php');
$stage = $data['stage'] ?? 1;
$action = $data['a'];
$showHidden = $data['h'] ?? 0;
$gymareaId = $data['ga'] ?? false;
$firstLetter = $data['fl'] ?? '';

// Get the keys if nothing was returned.
$keys_and_gymarea = gymMenu($action, $showHidden, $stage, $firstLetter, $gymareaId);
$keys = $keys_and_gymarea['keys'];
$msg = $keys_and_gymarea['gymareaTitle'];

// Build callback message string.
$callback_response = 'OK';

// Telegram JSON array.
$tg_json = array();

// Answer callback.
$tg_json[] = answerCallbackQuery($update['callback_query']['id'], $callback_response, true);

// Edit message.
$tg_json[] = edit_message($update, $msg, $keys, false, true);

// Telegram multicurl request.
curl_json_multi_request($tg_json);
