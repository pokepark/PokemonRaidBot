<?php
/**
 * Delete overview.
 * @param $chat_id
 * @param $message_id
 */
function delete_overview($chat_id, $message_id)
{
    global $db;

    // Delete telegram message.
    debug_log('Deleting overview telegram message ' . $message_id . ' from chat ' . $chat_id);
    delete_message($chat_id, $message_id);

    // Delete overview from database.
    debug_log('Deleting overview information from database for Chat_ID: ' . $chat_id);
    $rs = my_query(
        "
        DELETE FROM   overview
        WHERE   chat_id = '{$chat_id}'
        "
    );
}

?>
