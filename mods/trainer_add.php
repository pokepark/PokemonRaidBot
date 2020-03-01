<?php
// Write to log.
debug_log('TRAINER()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
bot_access_check($update, 'trainer-share');

// Init keys and chat list.
$keys = [];
$chat_list = '';

// $config->TRAINER_CHATS ?
if(!empty($config->TRAINER_CHATS)) {
    $chat_list = $config->TRAINER_CHATS;
    debug_log($chat_list, 'Added trainer chats to the chat list:');
}

// $config->SHARE_CHATS ?
if(!empty($config->SHARE_CHATS) && !empty($chat_list)) {
    $chat_list .= ',' . $config->SHARE_CHATS;
    debug_log($chat_list, 'Added share chats to the chat list:');
} else if(!empty($config->SHARE_CHATS) && empty($chat_list)) {
    $chat_list = $config->SHARE_CHATS;
    debug_log($chat_list, 'Added share chats to the chat list:');
}

// Get chats from config and add to keys.
for($i = 1; $i <= 6; $i++) {
    // Raid level adjustment
    if($i == 6) {
        $raid_level = 'X';
    } else {
        $raid_level = $i;
    }
    $const = 'SHARE_CHATS_LEVEL_' . $raid_level;
    $const_chats = $config->{$const};

    // Sharing keys for this raid level?
    if(!empty($const_chats)) {
        debug_log('Found chats by level, adding them');
        // Add chats. 
        if(!empty($chat_list)) {
            $chat_list .= ',' . $const_chats;
            debug_log($chat_list, 'Added ' . $const . ' chats to the chat list:');
        } else {
            $chat_list = $const_chats;
            debug_log($chat_list, 'Added ' . $const . ' chats to the chat list:');
        }
    }
}

// Delete duplicate chats.
debug_log($chat_list, 'Searching and removing duplicates from chat list:');
$chat_list = explode(',', $chat_list);
$chats = array_unique($chat_list);

// Get chats already in the database.
debug_log('Searching and removing chats already having the trainer message');
$rs = my_query(
    "
    SELECT    chat_id
    FROM      trainerinfo
    "
);

$chats_db = [];
while ($row = $rs->fetch_assoc()) {
    $chats_db[] = $row['chat_id'];
}
$log_chats_db = implode(',', $chats_db);

debug_log($log_chats_db, 'Chats already having the trainer message:');

$chats = array_diff($chats, $chats_db);
$chats = implode(',', $chats);
debug_log($chats, 'Chat list without duplicates:');

// Create keys.
if(!empty($chats)) {
    $keys = share_keys('0', 'trainer_share', $update, $chats, '', true);
}

// Add abort key.
if($keys) {
    // Add back navigation key.
    $nav_keys = [];
    $nav_keys[] = universal_inner_key($keys, '0', 'trainer', '0', getTranslation('back'));
    $nav_keys[] = universal_inner_key($keys, '0', 'exit', '0', getTranslation('abort'));

    // Get the inline key array.
    $keys[] = $nav_keys;

    // Set message.
    $msg = '<b>' . getTranslation('trainer_info_share_with_chat') . '</b>';
} else {
    // Set message.
    $msg = '<b>' . getTranslation('trainer_info_no_chats') . '</b>';
}

// Answer callback.
answerCallbackQuery($update['callback_query']['id'], 'OK');

// Edit message.
edit_message($update, $msg, $keys, false);

?>
