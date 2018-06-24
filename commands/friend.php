<?php
// Write to log.
debug_log('friend()');

// For debug.
//debug_log($update);
//debug_log($data);

// Get friend code.
$friend_code = trim(substr($update['message']['text'], 7));

// Write to log.
debug_log('SET friend code to ' . $friend_code);

// Private chat type.
if ($update['message']['chat']['type'] == 'private' || $update['callback_query']['message']['chat']['type'] == 'private') {
    // Update friend code in users table.
    my_query(
        "
        UPDATE    users
        SET       friend_code = '{$db->real_escape_string($friend_code)}'
          WHERE   user_id = {$update['message']['from']['id']}
        "
    );

    // Send the message.
    sendMessage($update['message']['chat']['id'], getTranslation('friend_code_update'));

} else {
    sendMessage($update['message']['chat']['id'], getTranslation('fail'));
    
}

exit();
