<?php
// Write to log.
debug_log('trainer_code()');
require_once(LOGIC_PATH . '/get_user.php');

// For debug.
//debug_log($update);
//debug_log($data);

// Access check.
$botUser->accessCheck('trainer');

// Mode and action
$action = $data['a'] ?? '';

// Set the user_id
$user_id = $update['callback_query']['from']['id'];

if($action == 'cancel') {
  my_query('DELETE FROM user_input WHERE user_id = ? AND handler = \'change_trainercode\'', [$user_id]);

  // Build callback message string.
  $callback_response = 'OK';

  $data['arg'] = $data['id'] = 0;
  require_once(ROOT_PATH . '/mods/trainer.php');
  exit;
}elseif($action == 'delete') {
  my_query('DELETE FROM user_input WHERE user_id = :user_id AND handler=\'change_trainercode\'', ['user_id' => $user_id]);
  my_query('
    UPDATE users
    SET trainercode = NULL
    WHERE user_id = ?
    ',[$user_id
  ]);

  // Build callback message string.
  $callback_response = 'OK';

  $data['arg'] = $data['id'] = 0;
  require_once(ROOT_PATH . '/mods/trainer.php');
  exit;
}
$user_data = get_user($user_id, false, true);
// Build message string.
$msg = '<b>' . getTranslation('your_trainer_info') . '</b>' . CR;
$msg .= $user_data['message'] . CR;

// Save the message id to db so we can delete it later
$modifiers = json_encode(['old_message_id'=>$update['callback_query']['message']['message_id']]);

$msg .= '<b>' . getTranslation('trainercode_select') . '</b>';
// Data for handling response from the user
my_query('INSERT INTO user_input SET user_id = ?, handler = \'change_trainercode\', modifiers = ?',[$user_id, $modifiers]);

// Build callback message string.
$callback_response = 'OK';

$keys[] = [
  button(getTranslation('back'), ['trainer_code', 'a' => 'cancel']),
  button(getTranslation('delete'), ['trainer_code', 'a' => 'delete'])
];
// Answer callback.
answerCallbackQuery($update['callback_query']['id'], $callback_response);

// Edit message.
edit_message($update, $msg, $keys, false);
