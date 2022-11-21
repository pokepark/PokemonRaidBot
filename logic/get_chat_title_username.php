<?php
/**
 * Return the title of the given chat_id.
 * @param int $chat_id
 * @return array [chat_title, chat_username]
 */
function get_chat_title_username($chat_id){
  // Get info about chat for title.
  debug_log('Getting chat object for chat_id: ' . $chat_id);
  $chat_obj = get_chat($chat_id);

  if(!isset($chat_obj['ok']) or $chat_obj['ok'] != 'true') {
    info_log('Getting chat title and username failed during overview construction for chat: ' . $chat_id);
    return ['',''];
  }
  // Set chat username if available.
  $chat_username = $chat_obj['result']['username'] ?? '';
  debug_log('Username of the chat: ' . $chat_username);

  // Set title.
  $chat_title = $chat_obj['result']['title'] ?? 'Unknown chat';
  debug_log('Title of the chat: ' . $chat_title);

  return [$chat_title, $chat_username];
}
