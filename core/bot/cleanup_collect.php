<?php

// Write to log.
cleanup_log('Collecting cleanup preparation information...');

// Init ID.
$cleanup_id = 0;

// Channel 
if(isset($update['channel_post']['text'])) {
    // Get chat_id and message_id
    $chat_id = $update['channel_post']['chat']['id'];
    $message_id = $update['channel_post']['message_id'];

    // Get id from text.
    $cleanup_id = substr(strrchr($update['channel_post']['text'], substr(strtoupper($config->BOT_ID), 0, 1) . '-ID = '), 7);

// Supergroup
} else if (isset($update['message']['text']) && $update['message']['chat']['type'] == "supergroup") {
    // Get chat_id and message_id
    $chat_id = $update['message']['chat']['id'];
    $message_id = $update['message']['message_id'];

    // Get id from text.
    $cleanup_id = substr(strrchr($update['message']['text'], substr(strtoupper($config->BOT_ID), 0, 1) . '-ID = '), 7);
}

if (function_exists('insert_cleanup')) {
    // Write cleanup info to database.
    cleanup_log('Calling cleanup preparation now!');
    cleanup_log('Cleanup_ID: ' . $cleanup_id);
    if($cleanup_id != 0) {
        insert_cleanup($chat_id, $message_id, $cleanup_id);
    }
} else {
    info_log('No function found to insert cleanup data to database!', 'ERROR:');
    info_log('Add a function named "insert_cleanup" to add cleanup info to the database!', 'ERROR:');
    info_log('Arguments of that function need to be the chat_id $chat_id, the message_id $message_id and the cleanup id $cleanup_id.', 'ERROR:');
    info_log('For example: function insert_cleanup($chat_id, $message_id, $cleanup_id)', 'ERROR:');
}

