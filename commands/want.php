<?php
// Write to log.
debug_log('want()');

// For debug.
//debug_log($update);
//debug_log($data);

// Get want code.
$want_code = trim(substr($update['message']['text'], 6));

// Write to log.
debug_log('SET want code to ' . $want_code);

// Private chat type.
if ($update['message']['chat']['type'] == 'private' || $update['callback_query']['message']['chat']['type'] == 'private') {
    // Update want code in users table.
    my_query(
        "
        UPDATE    users
        SET       want = '{$db->real_escape_string($want_code)}'
          WHERE   user_id = {$update['message']['from']['id']}
        "
    );

    // Send the message.
    sendMessage($update['message']['chat']['id'], getTranslation('want_update'));

} else {
    sendMessage($update['message']['chat']['id'], getTranslation('fail'));
    
}

exit();
