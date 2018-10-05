<?php
// Write to log.
debug_log('raid_set_poke()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check raid access.
raid_access_check($update, $data);

// Set the id.
$id = $data['id'];

// Update pokemon in the raid table.
my_query(
    "
    UPDATE    raids
    SET       pokemon = '{$data['arg']}'
      WHERE   id = {$id}
    "
);

// Get raid times.
$raid = get_raid($data['id']);

// Create the keys.
$keys = [];

// Build message string.
$msg = '';
$msg .= getTranslation('raid_saved') . CR;
$msg .= show_raid_poll_small($raid);

// Build callback message string.
$callback_response = getTranslation('raid_boss_saved');

// Answer callback.
answerCallbackQuery($update['callback_query']['id'], $callback_response);

// Edit message.
edit_message($update, $msg, $keys, false);

// Exit.
exit();
