<?php
// Write to log.
debug_log('trainer_code()');

// For debug.
//debug_log($update);
//debug_log($data);

// Access check.
bot_access_check($update, 'trainer');

// Confirmation and level
$confirm = $data['id'];
$trainercode = $data['arg'];
// Set the user_id
$user_id = $update['callback_query']['from']['id'];

// Ask for user level
if($confirm == 0) {

    // Build message string.
    $msg = '<b>' . getTranslation('your_trainer_info') . '</b>' . CR;
    $msg .= get_user($user_id) . CR;
    $msg .= '<b>' . getTranslation('trainercode_select') . '</b>';

    my_query(
        "
        UPDATE	  users
        SET       trainercode_time = DATE_ADD(NOW(), INTERVAL 1 HOUR)
        WHERE     user_id = {$user_id}
        "
    );

    // Build callback message string.
    $callback_response = 'OK';

    // Answer callback.
    answerCallbackQuery($update['callback_query']['id'], $callback_response);

    // Edit message.
    edit_message($update, $msg, $keys, false);

}

// Exit.
exit();

?>
