<?php
// Write to log.
debug_log('raids_delete()');

// For debug.
//debug_log($update);
//debug_log($data);

// Access check.
raid_access_check($update, $data, 'delete');

// Get the action.
// 0 -> Confirmation required
// 1 -> Cancel deletion
// 2 -> Execute deletion
$action = $data['arg'];

// Get the raid id.
$id = $data['id'];

// Execute the action.
if ($action == 0) {
    // Get raid.
    $raid = get_raid($id);

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
    
// Build callback message string.
$callback_response = 'OK';

// Telegram JSON array.
$tg_json = array();

// Answer callback.
$tg_json[] = answerCallbackQuery($update['callback_query']['id'], $callback_response, true);

// Edit message.
$tg_json[] = edit_message($update, $msg, $keys, false, true);

// Telegram multicurl request.
curl_json_multi_request($tg_json);

// Exit.
exit();
