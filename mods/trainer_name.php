<?php
// Write to log.
debug_log('trainer_name()');

// For debug.
//debug_log($update);
//debug_log($data);

// Access check.
$botUser->accessCheck($update, 'trainer');

// Mode and action
$mode = $data['id'];
$action = $data['arg'];

// Set the user_id
$user_id = $update['callback_query']['from']['id'];

if($action == "cancel") {
    my_query("DELETE FROM user_input WHERE user_id='{$user_id}' AND handler='change_trainername'");

    // Build callback message string.
    $callback_response = 'OK';

    $data['arg'] = $data['id'] = 0;
    require_once(ROOT_PATH . '/mods/trainer.php');
}elseif($action == "delete") {
    my_query("DELETE FROM user_input WHERE user_id='{$user_id}' AND handler='change_trainername'");
    my_query("        
        UPDATE users
        SET trainername =  NULL
        WHERE user_id = {$user_id}
    ");

    // Build callback message string.
    $callback_response = 'OK';

    $data['arg'] = $data['id'] = 0;
    require_once(ROOT_PATH . '/mods/trainer.php');
}elseif($action == "switch") {
    my_query("        
        UPDATE users
        SET display_name = IF(display_name = 0,1,0)
        WHERE user_id = {$user_id}
    ");

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
    my_query("INSERT INTO user_input SET user_id='{$user_id}', handler='change_trainername', modifiers='{$modifiers}' ");
}

// Build callback message string.
$callback_response = 'OK';
if($action != 'add') {
    if(!empty($user_data['row']['trainername'])) {
        $keys[] = [
                [
                    'text'          => getTranslation('switch_display_name'),
                    'callback_data' => '0:trainer_name:switch'
                ]
            ];
        $keys[] = [
                [
                    'text'          => getTranslation('trainername_edit'),
                    'callback_data' => '0:trainer_name:add'
                ],[
                    'text'          => getTranslation('delete'),
                    'callback_data' => '0:trainer_name:delete'
                ]
            ];
    }else {
        $keys[] = [
                [
                    'text'          => getTranslation('trainername_add'),
                    'callback_data' => '0:trainer_name:add'
                ]
            ];
    }
}
$keys[] = [
        [
            'text'          => getTranslation('back'),
            'callback_data' => $mode.':trainer_name:cancel'
        ]
    ];

    // Answer callback.
answerCallbackQuery($update['callback_query']['id'], $callback_response);

// Edit message.
edit_message($update, $msg, $keys, false);


// Exit.
$dbh = null;
exit();

?>
