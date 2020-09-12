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
if ($config->RAID_LOCATION) {
    // Send location.
    $msg_text = !empty($raid['address']) ? ($raid['address'] . ', ' . substr(strtoupper($config->BOT_ID), 0, 1) . '-ID = ' . $raid['id']) : ($raid['pokemon'] . ', ' . $raid['id']); // DO NOT REMOVE "ID ="--> NEEDED FOR $config->CLEANUP PREPARATION!
    $loc = send_venue($chat, $raid['lat'], $raid['lon'], '', $msg_text);

    // Write to log.
    debug_log('location:');
    debug_log($loc);
}

// Telegram JSON array.
$tg_json = array();

// Raid picture
if($config->RAID_PICTURE) {
  require_once(LOGIC_PATH . '/raid_picture.php');
  $picture_url = raid_picture_url($raid);
}

// Send the message.
$raid_picture_hide_level = explode(",",$config->RAID_PICTURE_HIDE_LEVEL);
$raid_picture_hide_pokemon = explode(",",$config->RAID_PICTURE_HIDE_POKEMON);

$raid_pokemon_id = $raid['pokemon'];
$raid_level = get_raid_level($raid['pokemon'], $raid['pokemon_form']);
$raid_pokemon_form_name = get_pokemon_form_name($raid_pokemon_id,$raid['pokemon_form_id']);
$raid_pokemon = $raid_pokemon_id . "-" . $raid_pokemon_form_name;

if($config->RAID_PICTURE && !in_array($raid_level, $raid_picture_hide_level) && !in_array($raid_pokemon, $raid_picture_hide_pokemon) && !in_array($raid_pokemon_id, $raid_picture_hide_pokemon)) {
    $tg_json[] = send_photo($chat, $picture_url, $text['short'], $keys, ['reply_to_message_id' => $chat, 'disable_web_page_preview' => 'true'], true);
} else {
    $tg_json[] = send_message($chat, $text['full'], ['inline_keyboard' => $keys], ['reply_to_message_id' => $chat, 'disable_web_page_preview' => 'true'], true);
}

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
