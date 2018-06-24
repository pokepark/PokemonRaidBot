<?php
// Write to log.
debug_log('have()');

// For debug.
//debug_log($update);
//debug_log($data);

// Get have code.
$have_code = trim(substr($update['message']['text'], 6));

// Write to log.
debug_log('SET have code to ' . $have_code);

// Private chat type.
if ($update['message']['chat']['type'] == 'private' || $update['callback_query']['message']['chat']['type'] == 'private') {
    // Update have code in users table.
    my_query(
        "
        UPDATE    users
        SET       have = '{$db->real_escape_string($have_code)}'
          WHERE   user_id = {$update['message']['from']['id']}
        "
    );

    // Send the message.
    sendMessage($update['message']['chat']['id'], getTranslation('have_update'));

} else {
    sendMessage($update['message']['chat']['id'], getTranslation('fail'));
    
}

exit();
