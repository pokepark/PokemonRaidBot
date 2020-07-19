<?php
/**
 * Insert overview.
 * @param $chat_id
 * @param $message_id
 */
function insert_overview($chat_id, $message_id)
{
    global $db;

    // Build query to check if overview details are already in database or not
    $rs = my_query(
        "
        SELECT    COUNT(*)
        FROM      overview
          WHERE   chat_id = '{$chat_id}'
         "
        );

    $row = $rs->fetch_row();

    // Overview already in database or new
    if (empty($row['0'])) {
        // Build query for overview table to add overview info to database
        debug_log('Adding new overview information to database overview list!');
        $rs = my_query(
            "
            INSERT INTO   overview
            SET           chat_id = '{$chat_id}',
                          message_id = '{$message_id}'
            "
        );
    } else {
        // Nothing to do - overview information is already in database.
        debug_log('Overview information is already in database! Nothing to do...');
    }
}

?>
