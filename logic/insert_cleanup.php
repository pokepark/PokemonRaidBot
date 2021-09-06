<?php
/**
 * Insert raid cleanup info to database.
 * @param $chat_id
 * @param $message_id
 * @param $raid_id
 * @param $type
 */
function insert_cleanup($chat_id, $message_id, $raid_id, $type)
{
    // Log ID's of raid, chat and message
    debug_log('Raid_ID: ' . $raid_id);
    debug_log('Chat_ID: ' . $chat_id);
    debug_log('Message_ID: ' . $message_id);
    debug_log('Type: ' . $type);

    if ((is_numeric($chat_id)) && (is_numeric($message_id)) && (is_numeric($raid_id)) && ($raid_id > 0)) {
        // Build query for cleanup table to add cleanup info to database
        debug_log('Adding cleanup info to database:');
        $rs = my_query(
            "
            INSERT INTO     cleanup
            SET             raid_id = '{$raid_id}',
                            chat_id = '{$chat_id}',
                            message_id = '{$message_id}',
                            type = '{$type}'
            "
        );
    } else {
        debug_log('Invalid input for cleanup preparation!');
    }
}

?>
