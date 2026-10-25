<?php
// Write to log.
debug_log('overview_delete()');
require_once(LOGIC_PATH . '/config_chats.php');

// For debug.
//debug_log($update);
//debug_log($data);

// Delete or list to deletion?
$action = $data['a'] ?? 0;
$overview_id = $data['o'] ?? null;

// Check access.
$botUser->accessCheck('overview');

// Telegram JSON array.
$tg_json = array();

// Get all or specific overview
if ($action == 0) {
  $request_overviews = my_query('
    SELECT  *
    FROM    overview
  ');

  while ($rowOverviews = $request_overviews->fetch()) {

    // Get info about chat for title.
    debug_log('Getting chat object for chat_id: ' . $rowOverviews['chat_id']);
    $chat_obj = get_config_chat_by_chat_and_thread_id($rowOverviews['chat_id'], $rowOverviews['thread_id']);
    if(!isset($chat_obj['title'])) {
      $chat_info = get_chat($rowOverviews['chat_id']);
      $chat_title = '';

      // Set title.
      if ($chat_info['ok'] == 'true') {
        $chat_title = $chat_info['result']['title'];
        debug_log('Title of the chat: ' . $chat_info['result']['title']);
      }
    }else {
      $chat_title = $chat_obj['title'];
    }

    // Build message string.
    $msg = '<b>' . getTranslation('delete_raid_overview_for_chat') . ' ' . $chat_title . '?</b>';

    // Set keys - Delete button.
    $keys[0][0] = button(getTranslation('yes'), ['overview_delete', 'o' => $rowOverviews['id'], 'a' => 3]);
    $keys[0][1] = button(getTranslation('no'), ['overview_delete', 'a' => 1]);

    // Send the message, but disable the web preview!
    $tg_json[] = send_message(create_chat_object([$update['callback_query']['message']['chat']['id']]), $msg, $keys, false, true);
  }

  // Set message.
  if($request_overviews->rowCount() == 0) {
    $callback_msg = '<b>' . getTranslation('no_overviews_found') . '</b>';
  } else {
    $callback_msg = '<b>' . getTranslation('list_all_overviews') . ':</b>';
  }
} else if ($action == 1) {
  // Write to log.
  debug_log('Deletion of the raid overview was canceled!');

  // Set message.
  $callback_msg = '<b>' . getTranslation('overview_deletion_was_canceled') . '</b>';
} else {

  // Get chat and message ids for overview.
  $request_overviews = my_query('
    SELECT  *
    FROM    overview
    WHERE   id = ?
    ', [$overview_id]
  );

  $overview = $request_overviews->fetch();

  // Delete overview
  $chat_id = $overview['chat_id'];
  $message_id = $overview['message_id'];
  // Write to log.
  debug_log('Triggering deletion of overview for Chat_ID ' . $chat_id);

  // Delete telegram message.
  debug_log('Deleting overview telegram message ' . $message_id . ' from chat ' . $chat_id);
  delete_message($chat_id, $message_id);

  // Delete overview from database.
  debug_log('Deleting overview information from database for Chat_ID: ' . $chat_id . ', thread_id: ' . $overview['thread_id']);
  $rs = my_query('
    DELETE FROM   overview
    WHERE   id = ?
    ', [$overview_id]
  );

  // Set message.
  $callback_msg = '<b>' . getTranslation('overview_successfully_deleted') . '</b>';
}

// Set keys.
$callback_keys = [];

// Build callback message string.
$callback_response = 'OK';

// Answer callback.
$tg_json[] = answerCallbackQuery($update['callback_query']['id'], $callback_response, true);

// Edit message.
$tg_json[] = edit_message($update, $callback_msg, $callback_keys, false, true);

// Telegram multicurl request.
curl_json_multi_request($tg_json);
