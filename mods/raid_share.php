<?php
// Write to log.
debug_log('raid_share()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check raid access.
raid_access_check($update, $data);

// Get raid id.
$id = $data['id'];

// Get chat id.
$chat = $data['arg'];

// Get raid data.
$raid = get_raid($id);

// Get text and keys.
$text = show_raid_poll($raid);
$keys = keys_vote($raid);

// Send location.
if (RAID_LOCATION == true) {
    // Send location.
    $msg_text = !empty($raid['address']) ? ($raid['address'] . ', R-ID = ' . $raid['id']) : ($raid['pokemon'] . ', ' . $raid['id']); // DO NOT REMOVE " R-ID = " --> NEEDED FOR CLEANUP PREPARATION!
    $loc = send_venue($chat, $raid['lat'], $raid['lon'], '', $msg_text);

    // Write to log.
    debug_log('location:');
    debug_log($loc);
}

// Send the message.
send_message($chat, $text, $keys, ['reply_to_message_id' => $chat, 'disable_web_page_preview' => 'true']);

// Set callback keys and message
$callback_msg = getTranslation('successfully_shared');
$callback_keys = array();
$callback_keys = [];

// Edit message.
edit_message($update, $callback_msg, $callback_keys, false);

// Answer callback.
answerCallbackQuery($update['callback_query']['id'], $callback_msg);

exit();
