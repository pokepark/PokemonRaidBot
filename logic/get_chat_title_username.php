<?php
/**
 * Return the title of the given chat_id.
 * @param $chat_id
 * @return array [chat_title, chat_username]
 */
function get_chat_title_username($chat_id){
    $chat_obj = get_chat($chat_id);
    $chat_username = '';

    // Set chat username if available.
    if ($chat_obj['ok'] == 'true' && isset($chat_obj['result']['username'])) {
        $chat_username = $chat_obj['result']['username'];
        debug_log('Username of the chat: ' . $chat_obj['result']['username']);
    }

    // Get info about chat for title.
    debug_log('Getting chat object for chat_id: ' . $chat_id);
    $chat_title = '<unknown chat>';

    // Set title.
    if ($chat_obj['ok'] == 'true' && !empty($chat_obj['result']['title'])) {
        $chat_title = $chat_obj['result']['title'];
        debug_log('Title of the chat: ' . $chat_obj['result']['title']);
    } else {
        debug_log($chat_obj, 'Unable to find title for ' . $chat_id  . ' from:');
    }
    return [$chat_title, $chat_username];
}

?>
