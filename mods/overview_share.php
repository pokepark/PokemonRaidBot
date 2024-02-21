<?php
// Write to log.
debug_log('overview_share()');
require_once(LOGIC_PATH . '/get_chat_title_username.php');
require_once(LOGIC_PATH . '/get_overview.php');
require_once(LOGIC_PATH . '/config_chats.php');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
$botUser->accessCheck('overview');

// Get chat ID from data
$chat_id = $data['c'] ?? NULL;

$tg_json = [];

// Share an overview
if($chat_id != NULL) {
  $query_chat = '';
  $chatObj = get_config_chat_by_short_id($chat_id);
  $query_chat = 'AND chat_id = ?';
  $binds[] = $chatObj['id'];
  if(isset($chatObj['thread'])) {
    $query_chat .= ' AND thread_id = ?';
    $binds[] = $chatObj['thread'];
  }
  // Get active raids.
  $request_active_raids = my_query('
    SELECT
      cleanup.chat_id, cleanup.thread_id, cleanup.message_id,
      raids.*,
      gyms.lat, gyms.lon, gyms.address, gyms.gym_name, gyms.ex_gym,
      TIME_FORMAT(TIMEDIFF(end_time, UTC_TIMESTAMP()) + INTERVAL 1 MINUTE, \'%k:%i\') AS t_left
    FROM    cleanup
    LEFT JOIN raids
    ON    raids.id = cleanup.raid_id
    LEFT JOIN gyms
    ON    raids.gym_id = gyms.id
    WHERE   raids.end_time>UTC_TIMESTAMP()
    ' . $query_chat . '
    ORDER BY  cleanup.chat_id, raids.end_time ASC, gyms.gym_name
  ', $binds);
  // Collect results in an array
  $active_raids = $request_active_raids->fetchAll();
  [$chat_title, $chat_username] = get_chat_title_username($chatObj['id']);
  $title = $chatObj['title'] ?? $chat_title;
  $overview_message = get_overview($active_raids, $title, $chat_username);
  // Shared overview
  $keys = [];

  // Set callback message string.
  $msg_callback = getTranslation('successfully_shared');

  // Answer the callback.
  $tg_json[] = answerCallbackQuery($update['callback_query']['id'], $msg_callback, true);

  // Edit the message, but disable the web preview!
  $tg_json[] = edit_message($update, $msg_callback, $keys, ['disable_web_page_preview' => 'true'], true);

  // Send the message, but disable the web preview!
  $tg_json[] = send_message($chatObj, $overview_message, $keys, ['disable_web_page_preview' => 'true'], true, 'overview');
  // Telegram multicurl request.
  curl_json_multi_request($tg_json);

  exit;
}
$keys = [];
// List all overviews to user
foreach( list_config_chats_by_short_id() as $short_id => $chat ) {
  $binds = [$chat['id']];
  $threadQuery = ' = ?';
  if(!isset($chat['thread']) or $chat['thread'] == 0) {
    $threadQuery = 'IS NULL';
  }else {
    $binds[] = $chat['thread'];
  }
  // Make sure it's not already shared
  $rs = my_query('
    SELECT  chat_id, thread_id, message_id, chat_title, chat_username
    FROM    overview
    WHERE   chat_id = ?
    AND     thread_id ' . $threadQuery . '
    LIMIT 1
    ', $binds
  );
  // Already shared
  if($rs->rowCount() > 0 ) continue;

  [$chat_title, $chat_username] = get_chat_title_username($chat['id']);
  $title = $chat['title'] ?? $chat_title;
  $keys[][] = button(getTranslation('share_with') . ' ' . $title, ['overview_share', 'c' => $short_id]);
}
// Set the callback message and keys
$msg = '<b>' . getTranslation('list_all_overviews') . ':</b>';
$keys[][] = button(getTranslation('abort'), ['exit', 'd' => '0']);

// Answer the callback.
$tg_json[] = answerCallbackQuery($update['callback_query']['id'], 'OK', true);

// Edit the message.
$tg_json[] = edit_message($update, $msg, $keys, false, true);

// Telegram multicurl request.
curl_json_multi_request($tg_json);
