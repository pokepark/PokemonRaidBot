<?php
// Write to log.
debug_log('raid_by_gym()');

// For debug.
//debug_log($update);
//debug_log($data);

// Get the first letter
$first = $data['arg'];

// Back key id, action and arg
$back_id = $data['id'];
$back_action = 'raid_by_gym_letter';
$back_arg = 0;

// Get the keys.
$keys = raid_edit_gym_keys($first);

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
} else {
    $keys = universal_key($keys, $back_id, $back_action, $back_arg, getTranslation('back'));
}

// Edit the message.
edit_message($update, getTranslation('select_gym_name'), $keys);

// Build callback message string.
$callback_response = getTranslation('here_we_go');

// Answer callback.
answerCallbackQuery($update['callback_query']['id'], $callback_response);

exit();
