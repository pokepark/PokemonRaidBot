<?php
// Write to log.
debug_log('raid_by_gym_letter()');

// For debug.
debug_log($update);
debug_log($data);

// Get the keys.
$keys = raid_edit_gyms_first_letter_keys();

// No keys found.
if (!$keys) {
    // Create the keys.
    $keys = [
        [
            [
                'text'          => getTranslation('not_supported'),
                'callback_data' => 'edit:not_supported'
            ]
        ]
    ];
}

// Edit the message.
edit_message($update, getTranslation('select_gym_first_letter'), $keys);

// Build callback message string.
$callback_response = getTranslation('select_gym');

// Answer callback.
answerCallbackQuery($update['callback_query']['id'], $callback_response);

exit();
