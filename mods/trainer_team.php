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
$confirm = $data['id'];
$team = $data['arg'];

// Set the user_id
$user_id = $update['callback_query']['from']['id'];

// Ask for user level
if($confirm == 0) {

  // Set keys.
  $keys = [
    [
      [
        'text'          => TEAM_B,
        'callback_data' => '1:trainer_team:mystic'
      ],
      [
        'text'          => TEAM_R,
        'callback_data' => '1:trainer_team:valor'
      ],
      [
        'text'          => TEAM_Y,
        'callback_data' => '1:trainer_team:instinct'
      ]
    ],
    [
      [
        'text'          => getTranslation('back'),
        'callback_data' => 'trainer'
      ],
      [
        'text'          => getTranslation('abort'),
        'callback_data' => 'exit'
      ]
    ]
  ];

  // Build message string.
  $msg = '<b>' . getTranslation('your_trainer_info') . '</b>' . CR;
  $msg .= get_user($user_id) . CR;
  $msg .= '<b>' . getTranslation('team_select') . '</b>';

  // Build callback message string.
  $callback_response = 'OK';

// Write team to database.
} else if($confirm == 1 && ($team == 'mystic' || $team == 'valor' || $team == 'instinct')) {
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
  $keys = [
    [
      [
        'text'          => getTranslation('back'),
        'callback_data' => 'trainer'
      ],
      [
        'text'          => getTranslation('done'),
        'callback_data' => formatCallbackData(['exit', 'd' => '1'])
      ]
    ]
  ];

}

// Answer callback.
answerCallbackQuery($update['callback_query']['id'], $callback_response);

// Edit message.
edit_message($update, $msg, $keys, false);

exit();
