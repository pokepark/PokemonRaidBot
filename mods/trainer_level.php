<?php
// Write to log.
debug_log('trainer_level()');
require_once(LOGIC_PATH . '/get_user.php');

// For debug.
//debug_log($update);
//debug_log($data);

// Access check.
$botUser->accessCheck('trainer');

$level = $data['l'] ?? 0;

// Set the user_id
$user_id = $update['callback_query']['from']['id'];

// Ask for user level
if($level == 0) {
  // Build message string.
  $msg = '<b>' . getTranslation('your_trainer_info') . '</b>' . CR;
  $msg .= get_user($user_id) . CR;
  $msg .= '<b>' . getTranslation('level_select') . '</b>';

  // Set keys.
  $keys = [];
  for($i = 5; $i <= $config->TRAINER_MAX_LEVEL; $i++) {
    $keys[] = button($i, ['trainer_level', 'l' => $i]);
  }

  // Get the inline key array.
  $keys = inline_key_array($keys, 5);

  // Add navigation keys.
  $keys[] = [
    button(getTranslation('back'), 'trainer'),
    button(getTranslation('done'), ['exit', 'd' => '1'])
  ];

  // Build callback message string.
  $callback_response = 'OK';

// Save user level
} else {

  // Update the user.
  my_query('
    UPDATE	  users
    SET     level = ?
    WHERE   user_id = ?
    ', [$level, $user_id]
  );

  // Build message string.
  $msg = '<b>' . getTranslation('level_saved') . '</b>' . CR . CR;
  $msg .= '<b>' . getTranslation('your_trainer_info') . '</b>' . CR;
  $msg .= get_user($user_id) . CR;

  // Build callback message string.
  $callback_response = 'OK';

  // Create the keys.
  $keys[] = [
    button(getTranslation('back'), 'trainer'),
    button(getTranslation('done'), ['exit', 'd' => '1'])
  ];
}

// Answer callback.
answerCallbackQuery($update['callback_query']['id'], $callback_response);

// Edit message.
edit_message($update, $msg, $keys, false);
