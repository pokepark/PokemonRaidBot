<?php
// Write to log.
debug_log('raid_share()');

// For debug.
//debug_log($update);
//debug_log($data);

// Access check.
raid_access_check($update, $data, 'share');

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
    $msg_text = !empty($raid['address']) ? ($raid['address'] . ', ' . substr(strtoupper(BOT_ID), 0, 1) . '-ID = ' . $raid['id']) : ($raid['pokemon'] . ', ' . $raid['id']); // DO NOT REMOVE "ID ="--> NEEDED FOR CLEANUP PREPARATION!
    $loc = send_venue($chat, $raid['lat'], $raid['lon'], '', $msg_text);

    // Write to log.
    debug_log('location:');
    debug_log($loc);
}

// Telegram JSON array.
$tg_json = array();

// Send the message.
$tg_json[] = send_message($chat, $text, $keys, ['reply_to_message_id' => $chat, 'disable_web_page_preview' => 'true'], true);

// Set callback keys and message
$callback_msg = getTranslation('successfully_shared');
$callback_keys = [];

// Answer callback.
$tg_json[] = answerCallbackQuery($update['callback_query']['id'], $callback_msg, true);

// Edit message.
$tg_json[] = edit_message($update, $callback_msg, $callback_keys, false, true);

// Telegram multicurl request.
curl_json_multi_request($tg_json);

// Exit.
exit();
