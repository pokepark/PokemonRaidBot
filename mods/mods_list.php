<?php
// Write to log.
debug_log('mods_list()');

// For debug.
//debug_log($update);
//debug_log($data);

// Set the id.
$user_id = $data['arg'];

if ($update['callback_query']['message']['chat']['type'] == 'private') {
    // Build message string.
    $msg = '';
    $msg .= getTranslation('mods_info_about_mod') . CR;

    // Add name.
    $msg .= get_user($user_id);

    // Create the keys.
    $keys = [];

    // Edit message.
    edit_message($update, $msg, $keys, false);

    // Build callback message string.
    $callback_response = 'OK';

    // Answer callback.
    answerCallbackQuery($update['callback_query']['id'], $callback_response);

}

exit();
