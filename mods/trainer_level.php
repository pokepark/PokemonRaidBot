<?php
// Write to log.
debug_log('trainer_level()');

// For debug.
//debug_log($update);
//debug_log($data);

// Access check.
bot_access_check($update, 'trainer');

// Confirmation and level
$confirm = $data['id'];
$level = $data['arg'];

// Set the user_id
$user_id = $update['callback_query']['from']['id'];

// Ask for user level
if($confirm == 0) {
    // Build message string.
    $msg = '<b>' . getTranslation('your_trainer_info') . '</b>' . CR;
    $msg .= get_user($user_id) . CR;
    $msg .= '<b>' . getTranslation('level_select') . '</b>';

    // Set keys.
    $keys = [];
    for($i = 5; $i <= 40; $i++) {
        $keys[] = array(
            'text'          => $i,
            'callback_data' => '1:trainer_level:' . $i
        );
    }

    // Get the inline key array.
    $keys = inline_key_array($keys, 5);

    // Add navigation keys.
    $nav_keys = [];
    $nav_keys[] = universal_inner_key($nav_keys, '0', 'trainer', '0', getTranslation('back'));
    $nav_keys[] = universal_inner_key($nav_keys, '0', 'exit', '0', getTranslation('abort'));
    $nav_keys = inline_key_array($nav_keys, 2);

    // Merge keys.
    $keys = array_merge($keys, $nav_keys);

    // Build callback message string.
    $callback_response = 'OK';

// Save user level
} else if($confirm == 1 && $level > 0) {

    // Update the user.
    my_query(
        "
        UPDATE	  users 
        SET       level = {$level}
        WHERE     user_id = {$user_id}
        "
    );

    // Build message string.
    $msg = '<b>' . getTranslation('level_saved') . '</b>' . CR . CR;
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

// Exit.
exit();
