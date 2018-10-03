<?php
// Write to log.
debug_log('GYM()');

// For debug.
//debug_log($update);
//debug_log($data);

// Get gym name.
$gym_name = trim(substr($update['message']['text'], 4));

// Write to log.
debug_log('Setting gym name to ' . $gym_name);

// Private chat type.
if ($update['message']['chat']['type'] == 'private') {
    // Update gym name in raid table.
    my_query(
        "
        UPDATE    gyms
        SET       gym_name = '{$db->real_escape_string($gym_name)}'
          WHERE   gym_name = '#{$update['message']['from']['id']}'
        ORDER BY  id DESC LIMIT 1
        "
    );

    // Send the message.
    sendMessage($update['message']['chat']['id'], getTranslation('gym_name_updated'));
}

// Exit.
exit();
