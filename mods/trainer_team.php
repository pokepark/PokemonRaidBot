<?php
// Write to log.
debug_log('trainer_team()');
require_once(LOGIC_PATH . '/get_user.php');

// For debug.
//debug_log($update);
//debug_log($data);

// Access check.
$botUser->accessCheck('trainer');

// Confirmation and level
$team = $data['t'] ?? '';

// Set the user_id
$user_id = $update['callback_query']['from']['id'];

// Ask for user level
if($team == '') {

  // Set keys.
  $keys[0][0] = button(TEAM_B, ['trainer_team', 't' => 'mystic']);
  $keys[0][1] = button(TEAM_R, ['trainer_team', 't' => 'valor']);
  $keys[0][2] = button(TEAM_Y, ['trainer_team', 't' => 'instinct']);
  $keys[1][0] = button(getTranslation('back'), 'trainer');
  $keys[1][1] = button(getTranslation('abort'), 'exit');

  // Build message string.
  $msg = '<b>' . getTranslation('your_trainer_info') . '</b>' . CR;
  $msg .= get_user($user_id) . CR;
  $msg .= '<b>' . getTranslation('team_select') . '</b>';

  // Build callback message string.
  $callback_response = 'OK';

// Write team to database.
} else if($team == 'mystic' || $team == 'valor' || $team == 'instinct') {
  // Update the user.
  my_query('
    UPDATE  users
    SET     team = ?
    WHERE   user_id = ?
    ', [$team, $user_id]
  );

  // Build message string.
  $msg = '<b>' . getTranslation('team_saved') . '</b>' . CR . CR;
  $msg .= '<b>' . getTranslation('your_trainer_info') . '</b>' . CR;
  $msg .= get_user($user_id) . CR;

  // Build callback message string.
  $callback_response = 'OK';

  // Create the keys.
  $keys[0][0] = button(getTranslation('back'), 'trainer');
  $keys[0][1] = button(getTranslation('done'), ['exit', 'd' => '1']);
}

// Answer callback.
answerCallbackQuery($update['callback_query']['id'], $callback_response);

// Edit message.
edit_message($update, $msg, $keys, false);

exit();
