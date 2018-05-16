<?php
// Write to log.
debug_log('raids_delete()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check raid access.
raid_access_check($update, $data);

// Get the action.
// 0 -> Confirmation required
// 1 -> Cancel deletion
// 2 -> Execute deletion
$action = $data['arg'];

// Get the raid id.
$id = $data['id'];

// Execute the action.
if ($action == 0) {
    // Build query.
    $request = my_query(
        "
        SELECT    *,
                  UNIX_TIMESTAMP(end_time)                        AS ts_end,
                  UNIX_TIMESTAMP(start_time)                      AS ts_start,
                  UNIX_TIMESTAMP(NOW())                           AS ts_now,
                  UNIX_TIMESTAMP(end_time)-UNIX_TIMESTAMP(NOW())  AS t_left
        FROM      raids
          WHERE   id = '{$id}' 
        "
    );

    // Get raid.
    $raid = $request->fetch_assoc();

    // Write to log.
    debug_log('Asking for confirmation to delete the following raid:');
    debug_log($raid);

    // Create keys array.
    $keys = [
        [
            [
                'text'          => getTranslation('yes'),
                'callback_data' => $raid['id'] . ':raids_delete:2'
            ],
            [
                'text'          => getTranslation('no'),
                'callback_data' => $raid['id'] . ':raids_delete:1'
            ]
        ]
    ];

    // Set message.
    $msg = EMOJI_WARN . '<b> ' . getTranslation('delete_this_raid') . ' </b>' . EMOJI_WARN . CR . CR;
    $msg .= show_raid_poll_small($raid);
} else if ($action == 1) {
    debug_log('Raid deletion for ' . $id . ' was canceled!');
    // Set message.
    $msg = '<b>' . getTranslation('raid_deletion_was_canceled') . '</b>';

    // Set keys.
    $keys = [];
} else if ($action == 2) {
    debug_log('Confirmation to delete raid ' . $id . ' was received!');
    // Set message.
    $msg = getTranslation('raid_successfully_deleted');

    // Set keys.
    $keys = [];

    // Delete raid from database.
    delete_raid($id);
}
    
// Edit message.
edit_message($update, $msg, $keys, false);

// Build callback message string.
$callback_response = 'OK';

// Answer callback.
answerCallbackQuery($update['callback_query']['id'], $callback_response);

exit;
