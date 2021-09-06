<?php
// Write to log.
debug_log('overview_delete()');

// For debug.
//debug_log($update);
//debug_log($data);

// Delete or list to deletion?
$chat_id = 0;
$chat_id = $data['arg'];

// Check access.
bot_access_check($update, 'overview');

// Telegram JSON array.
$tg_json = array();

// Get all or specific overview
if ($chat_id == 0) {
    $request_overviews = my_query(
        "
        SELECT    *
        FROM      overview
        "
    );

    // Count results.
    $count = 0;

    while ($rowOverviews = $request_overviews->fetch()) {
        // Counter++
        $count = $count + 1;

        // Get info about chat for title.
        debug_log('Getting chat object for chat_id: ' . $rowOverviews['chat_id']);
        $chat_obj = get_chat($rowOverviews['chat_id']);
        $chat_title = '';

        // Set title.
        if ($chat_obj['ok'] == 'true') {
            $chat_title = $chat_obj['result']['title'];
            debug_log('Title of the chat: ' . $chat_obj['result']['title']);
        }

        // Build message string.
        $msg = '<b>' . getTranslation('delete_raid_overview_for_chat') . ' ' . $chat_title . '?</b>';

        // Set keys - Delete button.
        $keys = [
            [
                [
                    'text'          => getTranslation('yes'),
                    'callback_data' => '0:overview_delete:' . $rowOverviews['chat_id']
                ],
                [
                    'text'          => getTranslation('no'),
                    'callback_data' => '0:overview_delete:1'
                ]
            ]
        ];

        // Send the message, but disable the web preview!
        $tg_json[] = send_message($update['callback_query']['message']['chat']['id'], $msg, $keys, false, true);
    }

    // Set message.
    if($count == 0) {
        $callback_msg = '<b>' . getTranslation('no_overviews_found') . '</b>';
    } else {
        $callback_msg = '<b>' . getTranslation('list_all_overviews') . ':</b>';
    }
} else if ($chat_id == 1) {
    // Write to log.
    debug_log('Deletion of the raid overview was canceled!');

    // Set message.
    $callback_msg = '<b>' . getTranslation('overview_deletion_was_canceled') . '</b>';
} else {
    // Write to log.
    debug_log('Triggering deletion of overview for Chat_ID ' . $chat_id);

    // Get chat and message ids for overview.
    $request_overviews = my_query(
        "
        SELECT    *
        FROM      overview
        WHERE     chat_id = '{$chat_id}'
        "
    );

    $overview = $request_overviews->fetch();

    // Delete overview
    $chat_id = $overview['chat_id'];
    $message_id = $overview['message_id'];

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

    // Set message.
    $callback_msg = '<b>' . getTranslation('overview_successfully_deleted') . '</b>';
}

// Set keys.
$callback_keys = [];

// Build callback message string.
$callback_response = 'OK';

// Answer callback.
$tg_json[] = answerCallbackQuery($update['callback_query']['id'], $callback_response, true);

// Edit message.
$tg_json[] = edit_message($update, $callback_msg, $callback_keys, false, true);

// Telegram multicurl request.
curl_json_multi_request($tg_json);

// Exit.
$dbh = null;
exit();
