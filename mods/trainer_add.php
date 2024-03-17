<?php
// Write to log.
debug_log('TRAINER()');
require_once(LOGIC_PATH . '/config_chats.php');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
$botUser->accessCheck('trainer-share');

// Init keys and chat list.
$keys = [];

$chats = list_config_chats_by_short_id();

// Get chats already in the database.
debug_log('Searching and removing chats already having the trainer message');
$rs = my_query('
  SELECT  chat_id, thread_id
  FROM    trainerinfo
');

$chats_db = $rs->fetchAll();
for($i=0;$i<count($chats);$i++) {
  foreach($chats_db as $chat_db) {
    if(
      $chats[$i]['id'] == $chat_db['chat_id'] && !isset($chats[$i]['thread']) ||
      ($chats[$i]['id'] == $chat_db['chat_id'] && isset($chats[$i]['thread']) && $chats[$i]['thread'] == $chat_db['thread_id'])
      ) {
      unset($chats[$i]);
    }
  }
}

// Create keys.
foreach($chats as $chatShortId => $chat) {
  // Get chat object
  debug_log("Getting chat object for '" . $chat['id'] . "'");
  $chat_obj = get_chat($chat['id']);

  // Check chat object for proper response.
  if ($chat_obj['ok'] != true) {
    info_log($chat, 'Invalid chat id in your configuration:');
    continue;
  }
  $chatTitle = $chat['title'] ?? $chat_obj['result']['title'];
  debug_log('Proper chat object received, continuing to add key for this chat: ' . $chatTitle);
  $shareData = [0 => 'trainer_share', 'c' => $chatShortId];
  $keys[][] = button(getTranslation('share_with') . ' ' . $chatTitle, $shareData);
}

// Add abort key.
if($keys) {
  // Add back navigation key.
  $nav_keys = [];
  $nav_keys[] = button(getTranslation('back'), 'trainer');
  $nav_keys[] = button(getTranslation('abort'), 'exit');

  // Get the inline key array.
  $keys[] = $nav_keys;

  // Set message.
  $msg = '<b>' . getTranslation('trainer_info_share_with_chat') . '</b>';
} else {
  // Set message.
  $msg = '<b>' . getTranslation('trainer_info_no_chats') . '</b>';
}

// Answer callback.
answerCallbackQuery($update['callback_query']['id'], 'OK');

// Edit message.
edit_message($update, $msg, $keys, false);
