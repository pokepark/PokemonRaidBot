<?php
// Write to log.
debug_log('trainer_name()');
require_once(LOGIC_PATH . '/get_user.php');

// For debug.
//debug_log($update);
//debug_log($data);

// Access check.
$botUser->accessCheck('trainer');

$action = $data['a'] ?? '';

// Set the user_id
$user_id = $botUser->userId;

if($action == 'cancel') {
  my_query('DELETE FROM user_input WHERE user_id = ? AND handler=\'change_trainername\'', [$user_id]);

  // Build callback message string.
  $callback_response = 'OK';

  $data['arg'] = $data['id'] = 0;
  require_once(ROOT_PATH . '/mods/trainer.php');
  exit;
}elseif($action == 'delete') {
  my_query('DELETE FROM user_input WHERE user_id = ? AND handler = \'change_trainername\'', [$user_id]);
  my_query('
    UPDATE users
    SET trainername =  NULL
    WHERE user_id = ?
  ', [$user_id]
  );

  // Build callback message string.
  $callback_response = 'OK';

  $data['arg'] = $data['id'] = 0;
  require_once(ROOT_PATH . '/mods/trainer.php');
}elseif($action == 'switch') {
  my_query('
    UPDATE users
    SET display_name = IF(display_name = 0,1,0)
    WHERE user_id = ?
    ', [$user_id]
  );

  // Build callback message string.
  $callback_response = 'OK';
}
$user_data = get_user($user_id, false, true);
// Build message string.
$msg = '<b>' . getTranslation('your_trainer_info') . '</b>' . CR;
$msg .= $user_data['message'] . CR;

// Save the message id to db so we can delete it later
$modifiers = json_encode(["old_message_id"=>$update['callback_query']['message']['message_id']]);

if($action == 'add') {
  $msg .= '<b>' . getTranslation('trainername_select') . '</b>';
  // Data for handling response from the user
  my_query('INSERT INTO user_input SET user_id = ?, handler = \'change_trainername\', modifiers = ?', [$user_id, $modifiers]);
}

// Build callback message string.
$callback_response = 'OK';
if($action != 'add') {
  if(!empty($user_data['row']['trainername'])) {
    $keys[] = [
        [
          'text'          => getTranslation('switch_display_name'),
          'callback_data' => formatCallbackData(['trainer_name', 'a' => 'switch'])
        ]
      ];
    $keys[] = [
        [
          'text'          => getTranslation('trainername_edit'),
          'callback_data' => formatCallbackData(['trainer_name', 'a' => 'add'])
        ],[
          'text'          => getTranslation('delete'),
          'callback_data' => formatCallbackData(['trainer_name', 'a' => 'delete'])
        ]
      ];
  }else {
    $keys[] = [
        [
          'text'          => getTranslation('trainername_add'),
          'callback_data' => formatCallbackData(['trainer_name', 'a' => 'add'])
        ]
      ];
  }
}
$keys[] = [
  [
    'text'          => getTranslation('back'),
    'callback_data' => formatCallbackData(['trainer_name', 'a' => 'cancel'])
  ]
];

  // Answer callback.
answerCallbackQuery($update['callback_query']['id'], $callback_response);

// Edit message.
edit_message($update, $msg, $keys, false);
