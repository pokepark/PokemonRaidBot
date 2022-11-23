<?php
// Write to log.
debug_log('gym_details()');
require_once(LOGIC_PATH . '/edit_gym_keys.php');
require_once(LOGIC_PATH . '/get_gym.php');
require_once(LOGIC_PATH . '/get_gym_details.php');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
$botUser->accessCheck('gym-details');

// Get the arg.
$arg = $data['g'];
$gymarea_id = $data['ga'] ?? false;

// Get the id.
$id = $data['g'];

// Get gym info.
$gym = get_gym($arg);
$msg = get_gym_details($gym, true);

$keys = edit_gym_keys($update, $arg, $gym['show_gym'], $gym['ex_gym'], $gym['gym_note'], $gym['address']);

// Build callback message string.
$callback_response = getTranslation('here_we_go');

// Telegram JSON array.
$tg_json = array();

// Answer callback.
$tg_json[] = answerCallbackQuery($update['callback_query']['id'], $callback_response, true);

// Edit the message.
$tg_json[] = edit_message($update, $msg, $keys, ['disable_web_page_preview' => 'true'], true);

// Telegram multicurl request.
curl_json_multi_request($tg_json);
