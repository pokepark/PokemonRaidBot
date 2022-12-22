<?php
require_once(LOGIC_PATH . '/keys_vote.php');
require_once(LOGIC_PATH . '/show_raid_poll.php');
/**
 * Update a raid poll to all relevant chats
 * If updating a specific poll, the pass the update variable containing json from Telegram, otherwise pass false
 * @param int $raid_id ID of the raid.
 * @param array|false $raid Array received from get_raid() (optional).
 * @param array|false $update
 * @param array|false $tg_json Multicurl array.
 * @param bool $update_photo Update the raid photo.
 * @return array|false tg_json multicurl array
 */

function update_raid_poll($raid_id, $raid = false, $update = false, $tg_json = false, $update_photo = false)
{
  global $config;
  $chat_and_message = [];
  // Parsing the update type here, since the callback_query can come from regular message or inline message
  if(isset($update['callback_query']['inline_message_id'])) {
    $chat_and_message[] = [
      'chat_id'    => null,
      'message_id'   => $update['callback_query']['inline_message_id'],
      'type'     => 'poll_text',
    ];
  // For updating a poll
  }else if(isset($update['push'])) {
    $chat_and_message[] = $update['push'];
  }
  // If neither of the methods above yielded results, or update came from a inline poll, check cleanup table for chat messages to update
  if(empty($chat_and_message) or isset($update['callback_query']['inline_message_id'])) {
    if($update_photo) $photo_query = 'AND (type = \'photo\' OR type = \'poll_photo\')'; else $photo_query = '';
    $rs_chann = my_query('
      SELECT chat_id, message_id, type, media_unique_id
      FROM cleanup
        WHERE raid_id = ?
        ' . $photo_query,[$raid_id]
    );
    if ($rs_chann->rowCount() > 0) {
      while($chat = $rs_chann->fetch()) {
        $chat_and_message[] = $chat;
      }
    }else {
      if($update === false) {
        if(is_array($tg_json)) return $tg_json;
        else return [];
      }
      $chatId = $update[$update['type']]['message']['chat']['id'];
      $messageId = $update[$update['type']]['message']['message_id'];
      if(isset($update[$update['type']]['message']['text']) && !empty($update[$update['type']]['message']['text'])) {
        $type = 'poll_text';
      }else if(isset($update[$update['type']]['message']['caption']) && !empty($update[$update['type']]['message']['caption'])) {
        $type = 'poll_photo';
      }else if(isset($update[$update['type']]['message']['venue']) && !empty($update[$update['type']]['message']['venue'])) {
        $type = 'poll_venue';
      }else if(isset($update[$update['type']]['message']['photo']) && !isset($update[$update['type']]['message']['caption'])) {
        $type = 'photo';
      }
      $unique_id = null;
      if(isset($update[$update['type']]['message']['photo'])) {
        $largest_size = 0;
        foreach($update[$update['type']]['message']['photo'] as $photo) {
          if($photo['file_size'] < $largest_size) continue;
          $largest_size = $photo['file_size'];
          $save_id = $photo['file_id'];
          $unique_id = $photo['file_unique_id'];
        }
      }
      $chat_and_message[] = ['chat_id' => $chatId, 'message_id' => $messageId, 'type' => $type, 'media_unique_id' => $unique_id];
    }
  }
  // Get the raid data by id.
  if($raid == false) $raid = get_raid($raid_id);

  // Message - make sure to not exceed Telegrams 1024 characters limit for caption
  $text = show_raid_poll($raid);
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
  $keys = keys_vote($raid);

  // Telegram JSON array.
  if($tg_json == false) $tg_json = [];

  foreach($chat_and_message as $chat_id_msg_id) {
    $chat = $chat_id_msg_id['chat_id'];
    $message = $chat_id_msg_id['message_id'];
    $type = $chat_id_msg_id['type'];
    if ($type == 'poll_text') {
      $raid_picture_hide_level = explode(",",$config->RAID_PICTURE_HIDE_LEVEL);
      $raid_picture_hide_pokemon = explode(",",$config->RAID_PICTURE_HIDE_POKEMON);

      // If poll type was text, and RAID_PICUTRE_AUTO_EXTEND is set true in config, we most likely want to update the poll with the short message
      // Exceptions are: inline poll (chat = null) and events with raid picture hidden
      if($config->RAID_PICTURE && $config->RAID_PICTURE_AUTOEXTEND && $raid['event_hide_raid_picture'] != 1 && $chat != null && !in_array($raid['level'], $raid_picture_hide_level) && !in_array($raid['pokemon'], $raid_picture_hide_pokemon) && !in_array($raid['pokemon'].'-'.$raid['pokemon_form'], $raid_picture_hide_pokemon)) {
        $tg_json[] = editMessageText($message, $text['short'], $keys, $chat, ['disable_web_page_preview' => 'true'], true);
      }else {
        $tg_json[] = editMessageText($message, $text['full'], $keys, $chat, ['disable_web_page_preview' => 'true'], true);
      }
      continue;
    }
    require_once(LOGIC_PATH . '/raid_picture.php');
    if($type == 'poll_photo') {
      // If the poll message gets too long, we'll replace it with regular text based poll
      if($post_text == true) {
        // Delete raid picture and caption.
        $tg_json[] = delete_message($chat, $message, true);
        my_query('DELETE FROM cleanup WHERE chat_id = ? AND message_id = ?', [$chat, $message]);

        $media_content = get_raid_picture($raid, true);
        $raid['standalone_photo'] = true; // Inject this into raid array so we can pass it all the way to photo cache
        // Resend raid poll as text message.
        send_photo($chat, $media_content[1], $media_content[0], '', [], [], false, $raid);
        send_message($chat, $text['full'], $keys, ['disable_web_page_preview' => 'true'], false, $raid_id);
        continue;
      }
      $media_content = get_raid_picture($raid);
      // Edit the picture and caption
      if(!isset($media_content[2]) or $media_content[2] != $chat_id_msg_id['media_unique_id']) {
        $tg_json[] = editMessageMedia($message, $text['short'], $media_content[1], $media_content[0], $keys, $chat, ['disable_web_page_preview' => 'true'], true, $raid);
      }else {
        // Edit the caption.
        $tg_json[] = editMessageCaption($message, $text['short'], $keys, $chat, ['disable_web_page_preview' => 'true'], true);
      }
    }else if ($type == 'photo' && $update_photo) {
      $media_content = get_raid_picture($raid, 1);
      $raid['standalone_photo'] = true; // Inject this into raid array so we can pass it all the way to photo cache
      if(!isset($media_content[2]) or $media_content[2] != $chat_id_msg_id['media_unique_id']) {
        $tg_json[] = editMessageMedia($message, '', $media_content[1], $media_content[0], false, $chat, false, true, $raid);
      }
    }
  }
  return $tg_json;
}
