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

// Edit the message.
edit_message($update, $msg, $keys);

// Answer callback.
answerCallbackQuery($update['callback_query']['id'], $msg);

exit();
