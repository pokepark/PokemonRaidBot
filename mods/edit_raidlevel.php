<?php
// Write to log.
debug_log('edit_raidlevel()');

// For debug.
//debug_log($update);
//debug_log($data);

// Get gym data via ID in arg
$gym_id = $data['arg'];
$gym = get_gym($gym_id);

// Back key id, action and arg
$back_id = 0;
$back_action = 'raid_by_gym';
$back_arg = $data['id'];
$gym_first_letter = $back_arg;

// Get the keys.
$keys = raid_edit_raidlevel_keys($gym_id, $gym_first_letter);

// No keys found.
if (!$keys) {
    // Create the keys.
    $keys = [
        [
            [
                'text'          => getTranslation('abort'),
                'callback_data' => '0:exit:0'
            ]
        ]
    ];
} else {
    // Add navigation keys.
    $nav_keys = [];
    $nav_keys[] = universal_inner_key($nav_keys, $back_id, $back_action, $back_arg, getTranslation('back'));
    $nav_keys[] = universal_inner_key($nav_keys, $gym_id, 'exit', '2', getTranslation('abort'));
    $nav_keys = inline_key_array($nav_keys, 2);
    // Merge keys.
    $keys = array_merge($keys, $nav_keys);
}

// Build message.
$msg = getTranslation('create_raid') . ': <i>' . $gym['address'] . '</i>';

// Build callback message string.
$callback_response = getTranslation('gym_saved');

// Answer callback.
answerCallbackQuery($update['callback_query']['id'], $callback_response);

// Edit the message.
edit_message($update, $msg . CR . getTranslation('select_raid_level') . ':', $keys);

// Exit.
exit();

