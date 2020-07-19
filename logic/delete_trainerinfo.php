<?php
/**
 * Delete trainerinfo.
 * @param $chat_id
 * @param $message_id
 */
function delete_trainerinfo($chat_id, $message_id)
{
    global $db;

    // Delete telegram message.
    debug_log('Deleting trainer info telegram message ' . $message_id . ' from chat ' . $chat_id);
    delete_message($chat_id, $message_id);

    // Delete trainer info from database.
    debug_log('Deleting trainer information from database for Chat_ID: ' . $chat_id);
    $rs = my_query(
        "
        DELETE FROM   trainerinfo
        WHERE   chat_id = '{$chat_id}'
        "
    );
}

?>
