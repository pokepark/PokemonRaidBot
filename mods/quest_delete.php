<?php
// Write to log.
debug_log('quest_delete()');

// For debug.
//debug_log($update);
//debug_log($data);

// Quest id.
$quest_id = $data['id'];

// Action.
$action = $data['arg'];

if ($action == 0) {
    // Write to log.
    debug_log('Asking for confirmation to delete the quest with ID: ' . $quest_id);

    // Create keys array.
    $keys = [
        [
            [
                'text'          => getTranslation('yes'),
                'callback_data' => $quest_id . ':quest_delete:' . '2'
            ],
            [
                'text'          => getTranslation('no'),
                'callback_data' => $quest_id . ':quest_delete:' . '1'
            ]
        ]
    ];

    // Set message.
    $msg = EMOJI_WARN . '<b> ' . getTranslation('delete_this_quest') . ' </b>' . EMOJI_WARN . CR . CR;
    $quest = get_quest($quest_id);
    $msg .= get_formatted_quest($quest);
} else if ($action == 1) {
    debug_log('Quest deletion for quest ID ' . $quest_id . ' was canceled!');
    // Set message.
    $msg = '<b>' . getTranslation('quest_deletion_was_canceled') . '</b>';

    // Set keys.
    $keys = [];
} else if ($action == 2) {
    debug_log('Confirmation to delete quest ' . $quest_id . ' was received!');
    // Set message.
    $msg = getTranslation('quest_successfully_deleted');

    // Set keys.
    $keys = [];

    // Delete quest.
    delete_quest($quest_id);
}

// Edit message.
edit_message($update, $msg, $keys, ['disable_web_page_preview' => 'true']);

// Build callback message string.
$callback_response = 'OK';

// Answer callback.
answerCallbackQuery($update['callback_query']['id'], $callback_response);

exit();
