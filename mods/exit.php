<?php
// Write to log.
debug_log('exit()');

// For debug.
//debug_log($update);
//debug_log($data);

// Set empty keys.
$keys = [];

// Build message string.
$msg = ($data['arg'] == 1) ? (getTranslation('done') . '!') : (getTranslation('action_aborted'));

// Answer callback.
answerCallbackQuery($update['callback_query']['id'], $msg);

// Edit the message.
edit_message($update, $msg, $keys);

// Set gym_user_id tag.
$gym_user_id = '#' . $update['callback_query']['from']['id'];

// Get gym.
$gym = get_gym($data['id']);

// Delete gym from database.
if($gym['gym_name'] == $gym_user_id && $gym['show_gym'] == 0 && $data['arg'] == 2) {
    delete_gym($data['id']);
}

// Exit.
exit();
