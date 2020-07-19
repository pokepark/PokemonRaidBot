<?php
/**
 * Delete raid.
 * @param $raid_id
 */
function delete_raid($raid_id)
{
    global $db;

    // Delete telegram messages for raid.
    $rs = my_query(
        "
        SELECT    *
            FROM      cleanup
            WHERE     raid_id = '{$raid_id}'
              AND     chat_id <> 0
        "
    );

    // Counter
    $counter = 0;

    // Delete every telegram message
    while ($row = $rs->fetch_assoc()) {
        // Delete telegram message.
        debug_log('Deleting telegram message ' . $row['message_id'] . ' from chat ' . $row['chat_id'] . ' for raid ' . $row['raid_id']);
        delete_message($row['chat_id'], $row['message_id']);
        $counter = $counter + 1;
    }

    // Nothing to delete on telegram.
    if ($counter == 0) {
        debug_log('Raid with ID ' . $raid_id . ' was not found in the cleanup table! Skipping deletion of telegram messages!');
    }

    // Delete raid from cleanup table.
    debug_log('Deleting raid ' . $raid_id . ' from the cleanup table:');
    $rs_cleanup = my_query(
        "
        DELETE FROM   cleanup
        WHERE   raid_id = '{$raid_id}'
           OR   cleaned = '{$raid_id}'
        "
    );

    // Delete raid from attendance table.
    debug_log('Deleting raid ' . $raid_id . ' from the attendance table:');
    $rs_attendance = my_query(
        "
        DELETE FROM   attendance
        WHERE  raid_id = '{$raid_id}'
        "
    );

    // Delete raid from raid table.
    debug_log('Deleting raid ' . $raid_id . ' from the raid table:');
    $rs_raid = my_query(
        "
        DELETE FROM   raids
        WHERE   id = '{$raid_id}'
        "
    );
}


?>
