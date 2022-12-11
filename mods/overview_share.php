<?php
// Write to log.
debug_log('overview_share()');
require_once(LOGIC_PATH . '/get_chat_title_username.php');
require_once(LOGIC_PATH . '/get_overview.php');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
$botUser->accessCheck('overview');

// Get chat ID from data
$chat_id = $data['c'] ?? 0;

// Get all or specific overview
$query_chat = '';
if ($chat_id != 0) {
  $query_chat = 'AND chat_id = \'' . $chat_id . '\'';
}
// Get active raids.
$request_active_raids = my_query('
  SELECT
    cleanup.chat_id, cleanup.message_id,
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
');
// Collect results in an array
$active_raids = $request_active_raids->fetchAll(PDO::FETCH_GROUP);

$tg_json = [];

// Share an overview
if($chat_id != 0) {
  [$chat_title, $chat_username] = get_chat_title_username($chat_id);
  $overview_message = get_overview($active_raids[$chat_id], $chat_title, $chat_username);
  // Shared overview
  $keys = [];

  // Set callback message string.
  $msg_callback = getTranslation('successfully_shared');

  // Answer the callback.
  $tg_json[] = answerCallbackQuery($update['callback_query']['id'], $msg_callback, true);

  // Edit the message, but disable the web preview!
  $tg_json[] = edit_message($update, $msg_callback, $keys, ['disable_web_page_preview' => 'true'], true);

  // Send the message, but disable the web preview!
  $tg_json[] = send_message($chat_id, $overview_message, $keys, ['disable_web_page_preview' => 'true'], true, 'overview');
  // Telegram multicurl request.
  curl_json_multi_request($tg_json);

  exit;
}
// List all overviews to user
foreach( array_keys($active_raids) as $chat_id ) {
  // Make sure it's not already shared
  $rs = my_query('
    SELECT  chat_id, message_id, chat_title, chat_username
    FROM    overview
    WHERE   chat_id = ?
    LIMIT 1
    ', [$chat_id]
  );
  $keys = [];
  // Already shared
  if($rs->rowCount() > 0 ) {
    $keys[] = [
      [
        'text'          => EMOJI_REFRESH,
        'callback_data' => '0:overview_refresh:' . $chat_id
      ],
      [
        'text'          => getTranslation('done'),
        'callback_data' => formatCallbackData(['exit', 'd' => '1'])
      ]
    ];
    $res = $rs->fetch();
    $chat_title = $res['chat_title'];
    $chat_username = $res['chat_username'];
  }else {
    [$chat_title, $chat_username] = get_chat_title_username($chat_id);
    $keys[] = [
      [
        'text'          => getTranslation('share_with') . ' ' . $chat_title,
        'callback_data' => formatCallbackData(['overview_share', 'c' => $chat_id])
      ]
    ];
  }
  $overview_message = get_overview($active_raids[$chat_id], $chat_title, $chat_username);
  // Send the message, but disable the web preview!
  $tg_json[] = send_message($update['callback_query']['message']['chat']['id'], $overview_message, $keys, ['disable_web_page_preview' => 'true'], true);
}
// Set the callback message and keys
$callback_keys = [];
$callback_msg = '<b>' . getTranslation('list_all_overviews') . ':</b>';

// Answer the callback.
$tg_json[] = answerCallbackQuery($update['callback_query']['id'], 'OK', true);

// Edit the message.
$tg_json[] = edit_message($update, $callback_msg, $callback_keys, false, true);

// Telegram multicurl request.
curl_json_multi_request($tg_json);
