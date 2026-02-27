<?php

// Write to log.
cleanup_log('Collecting cleanup preparation information...');

// Init ID.
$cleanup_id = 0;
$message = null;
// Channel
if(isset($update['channel_post']['text'])) {
  $message = $update['channel_post'];

// Supergroup
} else if (isset($update['message']['text']) && ($update['message']['chat']['type'] == "supergroup" || $update['message']['chat']['type'] == "group")) {
  $message = $update['message'];
}
if($message != null) {
  // Get chat_id and message_id
  $chat_id = $message['chat']['id'];
  $message_id = $message['message_id'];
  $thread_id = $message['message_thread_id'] ?? NULL;
  if(isset($message['reply_markup']['inline_keyboard'])) {
    $splitData = explode('|', $message['reply_markup']['inline_keyboard'][0][0]['callback_data']);
    // Search for raid id in the first button of the message
    for($i=1;$i<count($splitData);$i++) {
      $splitVariable = explode('=', $splitData[$i], 2);
      if(count($splitVariable) == 2 && $splitVariable[0] == 'r' && preg_match("^[0-9]+^", $splitVariable[1])) {
        $cleanup_id = $splitVariable[1];
        break;
      }
    }
  }else {
    // Get id from text.
    $idFromText = substr($message['text'],strpos($message['text'], substr(strtoupper($config->BOT_ID), 0, 1) . '-ID = ') + 7);
    if(preg_match("^[0-9]+^", $idFromText)) {
      $cleanup_id = $idFromText;
    }
  }

  // Write cleanup info to database.
  if($cleanup_id != 0) {
    cleanup_log('Calling cleanup preparation now!');
    cleanup_log('Cleanup_ID: ' . $cleanup_id);
    require_once(LOGIC_PATH . '/insert_cleanup.php');
    insert_cleanup($chat_id, $message_id, $thread_id, $cleanup_id, 'inline_poll_text');
  }
}
