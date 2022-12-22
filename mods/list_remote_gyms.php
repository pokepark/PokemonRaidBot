<?php
// Write to log.
debug_log('list_remote gyms()');

// For debug.
//debug_log($update);
//debug_log($data);

$user_id = $update['callback_query']['from']['id'];

// Get the keys.
$query_remote = my_query('SELECT raids.id, gyms.gym_name, raids.start_time, raids.end_time FROM gyms LEFT JOIN raids on raids.gym_id = gyms.id WHERE raids.end_time > (UTC_TIMESTAMP() - INTERVAL 10 MINUTE) AND temporary_gym = 1');
while($gym = $query_remote->fetch()) {
  $keys[][] = button($gym['gym_name'], ['list_raid', 'r' => $gym['id']]);
}
// Add navigation keys.
$nav_keys = [];
$nav_keys[] = button(getTranslation('back'), ['gymMenu', 'a' => 'list']);
$nav_keys[] = button(getTranslation('abort'), 'exit');
$nav_keys = inline_key_array($nav_keys, 2);
// Merge keys.
$keys = array_merge($keys, $nav_keys);

// Build callback message string.
$callback_response = getTranslation('here_we_go');

// Telegram JSON array.
$tg_json = array();

// Answer callback.
$tg_json[] = answerCallbackQuery($update['callback_query']['id'], $callback_response, true);

// Edit the message.
$tg_json[] = edit_message($update, getTranslation('select_gym_name'), $keys, false, true);

// Telegram multicurl request.
curl_json_multi_request($tg_json);
