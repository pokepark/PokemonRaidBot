<?php
// Write to log.
debug_log('TRAINER()');
require_once(LOGIC_PATH . '/get_chat_title_username.php');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
$botUser->accessCheck('trainer-delete');

// Init keys and chat list.
$keys = [];

// Get chat id and action
$trainer_chat = $data['id'];
$action = $data['arg'];

// Show chats to delete
if($action == 0 || $trainer_chat == 0) {
  debug_log('Getting chats the trainer message was shared with');
  $rs = my_query('
    SELECT  chat_id
    FROM    trainerinfo
  ');

  while ($row = $rs->fetch()) {
    // Chat and message ID
    $chat_id = $row['chat_id'];
    [$chat_title, $chat_username] = get_chat_title_username($chat_id);

    $keys[] = universal_inner_key($keys, $chat_id, 'trainer_delete', '1', $chat_title);
  }

  // Add abort key.
  if($keys) {
    // Inline key array.
    $keys = inline_key_array($keys, 1);

    // Add back navigation key.
    $nav_keys = [];
    $nav_keys[] = universal_inner_key($keys, '0', 'trainer', '0', getTranslation('back'));
    $nav_keys[] = universal_inner_key($keys, '0', 'exit', '0', getTranslation('abort'));

    // Get the inline key array.
    $keys[] = $nav_keys;

    // Set message.
    $msg = '<b>' . getTranslation('trainer_message_delete') . '?</b>';
  } else {
    // Set message.
    $msg = '<b>' . getTranslation('trainer_info_no_chats') . '</b>';
  }

// Confirm deletion
} else if($action == 1 && $trainer_chat != 0) {
  [$chat_title, $chat_username] = get_chat_title_username($trainer_chat);

  // Set message
  $msg = $chat_title . CR . CR;
  $msg .= EMOJI_WARN . SP . '<b>' . getTranslation('delete_trainer_message_from_chat') . '</b>' . SP . EMOJI_WARN;

  // Create the keys.
  $keys = [
    [
      [
        'text'          => getTranslation('yes'),
        'callback_data' => $trainer_chat . ':trainer_delete:2'
      ]
    ],
    [
      [
        'text'          => getTranslation('no'),
        'callback_data' => 'exit'
      ]
    ]
  ];

// Delete trainer message
} else if($action == 2 && $trainer_chat != 0) {
  require_once(LOGIC_PATH . '/delete_trainerinfo.php');
  debug_log('Deleting trainer message from chat ' . $trainer_chat);
  [$chat_title, $chat_username] = get_chat_title_username($trainer_chat);

  // Set message
  $msg = '<b>' . getTranslation('deleted_trainer_message') . '</b>' . CR;

  // Get trainer messages
  debug_log('Getting chats the trainer message was shared with');
  $rs = my_query('
    SELECT  message_id
    FROM    trainerinfo
    WHERE   chat_id = ?
    ', [$trainer_chat]
  );

  // Delete trainer message.
  while ($row = $rs->fetch()) {
    delete_trainerinfo($trainer_chat, $row['message_id']);
  }
}

// Answer callback.
answerCallbackQuery($update['callback_query']['id'], 'OK');

// Edit message.
edit_message($update, $msg, $keys, false);
