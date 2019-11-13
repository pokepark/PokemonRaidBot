<?php
// Write to log.
debug_log('TRAINER()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
bot_access_check($update, 'trainer-delete');

// Init keys and chat list.
$keys = [];

// Get chat id and action
$trainer_chat = $data['id'];
$action = $data['arg'];

// Show chats to delete
if($action == 0 || $trainer_chat == 0) {
    debug_log('Getting chats the trainer message was shared with');
    $rs = my_query(
        "
        SELECT    *
        FROM      trainerinfo
        "
    );

    while ($row = $rs->fetch_assoc()) {
        // Chat and message ID
        $chat_id = $row['chat_id'];
        $message_id = $row['message_id'];

        // Get info about chat for title.
        debug_log('Getting chat object for chat_id: ' . $chat_id);
        $chat_obj = get_chat($chat_id);
        $chat_title = '';

        // Set title.
        if ($chat_obj['ok'] == 'true') {
            $chat_title = $chat_obj['result']['title'];
            debug_log('Title of the chat: ' . $chat_obj['result']['title']);
        } else {
            $chat_title = $chat_id;
        }

        $keys[] = universal_inner_key($keys, $chat_id, 'trainer_delete', '1', $chat_title);
    }

    // Add abort key.
    if($keys) {
        // Inline key array.
        $keys = inline_key_array($keys, 1);

        // Add back navigation key.
        $nav_keys = [];
        $nav_keys[] = universal_inner_key($keys, '0', 'trainer', '0', getTranslation('back'));
        $nav_keys[] = universal_inner_key($keys, '0', 'exit', '0', getTranslation('abort'));

        // Get the inline key array.
        $keys[] = $nav_keys;

        // Set message.
        $msg = '<b>' . getTranslation('trainer_message_delete') . '?</b>';
    } else {
        // Set message.
        $msg = '<b>' . getTranslation('trainer_info_no_chats') . '</b>';
    }

// Confirm deletion
} else if($action == 1 && $trainer_chat != 0) {
    // Get info about chat for title.
    debug_log('Getting chat object for chat_id: ' . $trainer_chat);
    $chat_obj = get_chat($trainer_chat);
    $chat_title = '';

    // Set title.
    if ($chat_obj['ok'] == 'true') {
        $chat_title = $chat_obj['result']['title'];
        debug_log('Title of the chat: ' . $chat_obj['result']['title']);
    } else {
        $chat_title = $trainer_chat;
    }

    // Set message
    $msg = $chat_title . CR . CR;
    $msg .= EMOJI_WARN . SP . '<b>' . getTranslation('delete_trainer_message_from_chat') . '</b>' . SP . EMOJI_WARN;

    // Create the keys.
    $keys = [
        [
            [
                'text'          => getTranslation('yes'),
                'callback_data' => $trainer_chat . ':trainer_delete:2'
            ]
        ],
        [
            [
                'text'          => getTranslation('no'),
                'callback_data' => '0:exit:0'
            ]
        ]
    ];

// Delete trainer message
} else if($action == 2 && $trainer_chat != 0) {
    debug_log('Deleting trainer message from chat ' . $trainer_chat);
    // Get info about chat for title.
    debug_log('Getting chat object for chat_id: ' . $trainer_chat);
    $chat_obj = get_chat($trainer_chat);
    $chat_title = '';

    // Set title.
    if ($chat_obj['ok'] == 'true') {
        $chat_title = $chat_obj['result']['title'];
        debug_log('Title of the chat: ' . $chat_obj['result']['title']);
    } else {
        $chat_title = $trainer_chat;
    }

    // Set message
    $msg = '<b>' . getTranslation('deleted_trainer_message') . '</b>' . CR;

    // Get trainer messages
    debug_log('Getting chats the trainer message was shared with');
    $rs = my_query(
        "
        SELECT    *
        FROM      trainerinfo
        WHERE     chat_id = '{$trainer_chat}'
        "
    );

    // Delete trainer message.
    while ($row = $rs->fetch_assoc()) {
        delete_trainerinfo($row['chat_id'], $row['message_id']);
    }
}

// Answer callback.
answerCallbackQuery($update['callback_query']['id'], 'OK');

// Edit message.
edit_message($update, $msg, $keys, false);

?>
