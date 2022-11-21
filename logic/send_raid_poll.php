<?php
require_once(LOGIC_PATH . '/get_pokemon_form_name.php');
require_once(LOGIC_PATH . '/keys_vote.php');
require_once(LOGIC_PATH . '/show_raid_poll.php');
/**
 * Post a raid poll to all relevant chats
 * @param int $raid_id ID of the raid
 * @param int|array $shareChats chat ID or array of IDs
 * @param array|false $raid Array received from get_raid() (optional).
 * @param array|false $tg_json multicurl array
 * @return array multicurl array
 */

function send_raid_poll($raid_id, $shareChats, $raid = false, $tg_json = false) {
  global $config;
  // Telegram JSON array.
  if($tg_json == false) $tg_json = [];
  if(!is_array($shareChats)) $shareChats = [$shareChats];
  // Check if Raid has been posted to target chat
  $resultChats = my_query('
    SELECT DISTINCT chat_id
    FROM cleanup
    WHERE raid_id = :raidId
    AND chat_id IN ("' . implode('","',$shareChats) . '")
    ',
    [
      'raidId' => $raid_id,
    ]);
  $chatsAlreadySharedTo = $resultChats->fetchAll(PDO::FETCH_COLUMN, 0);
  $chats = array_diff($shareChats, $chatsAlreadySharedTo);
  if(count($chats) == 0) return $tg_json;

  // Get raid data.
  if($raid == false) $raid = get_raid($raid_id);

  // Get text and keys.
  $text = show_raid_poll($raid);
  $keys = keys_vote($raid);

  $post_text = false;
  if(array_key_exists('short', $text)) {
    $msg_short_len = strlen(utf8_decode($text['short']));
    debug_log($msg_short_len, 'Raid poll short message length:');
    // Message short enough?
    if($msg_short_len >= 1024) {
      // Use full text and reset text to true regardless of prior value
      $post_text = true;
    }
  } else {
    // Use full text and reset text to true regardless of prior value
    $post_text = true;
  }

  // Send the message.
  $raid_picture_hide_level = explode(",",$config->RAID_PICTURE_HIDE_LEVEL);
  $raid_picture_hide_pokemon = explode(",",$config->RAID_PICTURE_HIDE_POKEMON);
  $raid_poll_hide_buttons_levels = explode(",",$config->RAID_POLL_HIDE_BUTTONS_RAID_LEVEL);

  $raid_pokemon_id = $raid['pokemon'];
  $raid_level = $raid['level'];
  $raid_pokemon_form_name = get_pokemon_form_name($raid_pokemon_id,$raid['pokemon_form']);
  $raid_pokemon = $raid_pokemon_id . "-" . $raid_pokemon_form_name;

  foreach($chats as $chat_id) {
    if ($config->RAID_LOCATION) {
      // Send location.
      $msg_text = !empty($raid['address']) ? $raid['address'] : $raid['pokemon'];
      // Sending venue together with raid poll can't be multicurled since they would appear to the chat in random order
      send_venue($chat_id, $raid['lat'], $raid['lon'], '', $msg_text, false, false, $raid_id);
      send_message($chat_id, $text['full'], $keys, ['disable_web_page_preview' => 'true'], false, $raid_id);
      continue;
    }
    if(!$config->RAID_PICTURE || $raid['event_hide_raid_picture'] == 1 || in_array($raid_level, $raid_picture_hide_level) || in_array($raid_pokemon, $raid_picture_hide_pokemon) || in_array($raid_pokemon_id, $raid_picture_hide_pokemon)) {
      $tg_json[] = send_message($chat_id, $text['full'], $keys, ['disable_web_page_preview' => 'true'], true, $raid_id);
      continue;
    }
    require_once(LOGIC_PATH . '/raid_picture.php');
    $media_content = get_raid_picture($raid, $config->RAID_PICTURE_AUTOEXTEND);
    if((!$config->RAID_PICTURE_AUTOEXTEND || in_array($raid['level'], $raid_poll_hide_buttons_levels)) or !$post_text) {
      $tg_json[] = send_photo($chat_id, $media_content[1], $media_content[0], $text['short'], $keys, ['disable_web_page_preview' => 'true'], true, $raid);
      continue;
    }
    $raid['standalone_photo'] = true; // Inject this into raid array so we can pass it all the way to photo cache
    send_photo($chat_id, $media_content[1], $media_content[0], '', [], [], false, $raid);
    send_message($chat_id, $text['short'], $keys, ['disable_web_page_preview' => 'true'], false, $raid_id);
  }
  return $tg_json;
}
