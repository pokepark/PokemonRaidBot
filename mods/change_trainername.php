<?php

// get UserID from Message
$userid = $update['message']['from']['id'];

$returnValue = preg_match('/^[A-Za-z0-9]{0,15}$/', $update['message']['text']);
// Only numbers and alphabetic character allowed
if(!$returnValue){
  // Trainer Name got unallowed Chars -> Error-Message
  send_message($userid, getTranslation('trainername_fail'));
  exit();
}
$trainername = $update['message']['text'];
// Store new Gamer-Name to DB
my_query('
  UPDATE users
  SET trainername = ?,
    display_name = IF(trainername is null, 0, 1)
  WHERE user_id = ?
  ', [$trainername, $userid]
);

// Remove back button from previous message to avoid confusion
edit_message_keyboard($modifiers['old_message_id'], [], $userid);

// Create the keys.
$keys = [
  [
    [
      'text'          => getTranslation('back'),
      'callback_data' => '0:trainer:0'
    ],
    [
      'text'          => getTranslation('done'),
      'callback_data' => '0:exit:1'
    ]
  ]
];

// confirm Name-Change
send_message($userid, getTranslation('trainername_success').' <b>'.$trainername.'</b>', $keys);
