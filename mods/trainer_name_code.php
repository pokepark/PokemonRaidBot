<?php
// Write to log.
debug_log('trainer_name_code()');

// For debug.
//debug_log($update);
//debug_log($data);

// Access check.
bot_access_check($update, 'trainer');

// Mode and action
$mode = $data['id'];
$action = $data['arg'];

// Set the user_id
$user_id = $update['callback_query']['from']['id'];

// Mode
// 1 = trainername
// 2 = trainercode
if($mode == 1) {
    $handler = "change_trainername";
    $translation = "trainername_select";
    $sql_col = "trainername";
}else if($mode == 2) {
    $handler = "change_trainercode";
    $translation = "trainercode_select";
    $sql_col = "trainercode";
}

if($action == "cancel") {
    my_query("DELETE FROM user_input WHERE user_id='{$user_id}' AND handler='{$handler}'");

    // Build callback message string.
    $callback_response = 'OK';
    
    $data['arg'] = $data['id'] = 0;
    require_once(ROOT_PATH . '/mods/trainer.php');
}elseif($action == "delete") {
    my_query("DELETE FROM user_input WHERE user_id='{$user_id}' AND handler='{$handler}'");
    my_query("        
        UPDATE users
        SET {$sql_col} =  NULL
        WHERE user_id = {$user_id}
    ");

    // Build callback message string.
    $callback_response = 'OK';
    
    $data['arg'] = $data['id'] = 0;
    require_once(ROOT_PATH . '/mods/trainer.php');
}else {
    // Build message string.
    $msg = '<b>' . getTranslation('your_trainer_info') . '</b>' . CR;
    $msg .= get_user($user_id) . CR;
    $msg .= '<b>' . getTranslation($translation) . '</b>';
    
    // Save the message id to db so we can delete it later
    $modifiers = json_encode(["old_message_id"=>$update['callback_query']['message']['message_id']]);
    
    // Data for handling response from the user
    my_query("INSERT INTO user_input SET user_id='{$user_id}', handler='{$handler}', modifiers='{$modifiers}' ");
    
    // Build callback message string.
    $callback_response = 'OK';

    $keys[] = [
            [
                'text'          => getTranslation('back'),
                'callback_data' => $mode.':trainer_name_code:cancel'
            ],[
                'text'          => getTranslation('delete'),
                'callback_data' => $mode.':trainer_name_code:delete'
            ]
        ];
    // Answer callback.
    answerCallbackQuery($update['callback_query']['id'], $callback_response);

    // Edit message.
    edit_message($update, $msg, $keys, false);
}

// Exit.
$dbh = null;
exit();

?>
