<?php
// Write to log.
debug_log('QUEST()');

// For debug.
//debug_log($update);
//debug_log($data);

// Get pokestops by name.
$searchterm = trim(substr($update['message']['text'], 6));

// Get all matching pokestops.
$keys = get_pokestop_list_keys($searchterm);

// Keys array received?
if (is_array($keys)) {
    // Set message.
    $msg = '<b>' . getTranslation('quest_by_pokestop') . '</b>';
} else if ($keys == false) {
    // Set message.
    $msg = '<b>' . getTranslation('pokestops_not_found') . '</b>' . CR . CR . getTranslation('pokestops_not_found_command_text') . SP . getTranslation('pokestops_not_found_command_example');

    // Set empty keys.
    $keys = [];
} else {
    // Set message.
    $msg = '<b>' . getTranslation('pokestops_not_found') . '</b>';

    // Set empty keys.
    $keys = [];
}

// Send message.
send_message($update['message']['chat']['id'], $msg, $keys, ['reply_markup' => ['selective' => true, 'one_time_keyboard' => true]]);

exit();
