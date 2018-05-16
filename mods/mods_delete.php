<?php
// Write to log.
debug_log('mods_delete()');

// For debug.
//debug_log($update);
//debug_log($data);

// Set the id.
$user_id = $data['arg'];

if ($update['callback_query']['message']['chat']['type'] == 'private') {
    // Update the user.
    my_query(
        "
        UPDATE	  users 
        SET       moderator = 0
                  WHERE   user_id = {$user_id}
        "
    );

    // Build message string.
    $msg = '';
    $msg .= '<b>' . getTranslation('mods_delete_mod') . '</b>' . CR;
    $msg .= get_user($user_id);

    // Create the keys.
    $keys = [];

    // Edit message.
    edit_message($update, $msg, $keys, false);

    // Build callback message string.
    $callback_response = getTranslation('mods_delete_mod');

    // Answer callback.
    answerCallbackQuery($update['callback_query']['id'], $callback_response);

}

exit();
