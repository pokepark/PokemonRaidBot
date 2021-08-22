<?php

// Write to log.
cleanup_log('Collecting cleanup preparation information...');

// Init ID.
$cleanup_id = 0;
$message = null;
// Channel 
if(isset($update['channel_post']['text'])) {
    $message = $update['channel_post'];

// Supergroup
} else if (isset($update['message']['text']) && ($update['message']['chat']['type'] == "supergroup" || $update['message']['chat']['type'] == "group")) {
    $message = $update['message'];
}
if($message != null) {
    // Get chat_id and message_id
    $chat_id = $message['chat']['id'];
    $message_id = $message['message_id'];
    if(isset($message['reply_markup']['inline_keyboard'])) {
        $split_data = explode(':', $message['reply_markup']['inline_keyboard'][0][0]['callback_data']);
        $cleanup_id = $split_data[0];
    }else {
        // Get id from text.
        $cleanup_id = substr($message['text'],strpos($message['text'], substr(strtoupper($config->BOT_ID), 0, 1) . '-ID = ') + 7);
    }

    // Write cleanup info to database.
    cleanup_log('Calling cleanup preparation now!');
    cleanup_log('Cleanup_ID: ' . $cleanup_id);
    if($cleanup_id != 0) {
        insert_cleanup($chat_id, $message_id, $cleanup_id, 'inline_poll_text');
    }
}
