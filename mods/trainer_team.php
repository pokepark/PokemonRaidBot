<?php
// Write to log.
debug_log('trainer_team()');

// For debug.
//debug_log($update);
//debug_log($data);

// Access check.
$botUser->accessCheck($update, 'trainer');

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
                'callback_data' => '0:trainer:0'
            ],
            [
                'text'          => getTranslation('abort'),
                'callback_data' => '0:exit:0'
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
    my_query(
        "
        UPDATE	  users 
        SET       team = '{$team}'
        WHERE     user_id = {$user_id}
        "
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
                'callback_data' => '0:trainer:0'
            ],
            [
                'text'          => getTranslation('done'),
                'callback_data' => '0:exit:1'
            ]
        ]
    ];

}

// Answer callback.
answerCallbackQuery($update['callback_query']['id'], $callback_response);

// Edit message.
edit_message($update, $msg, $keys, false);

exit();
