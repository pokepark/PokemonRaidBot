<?php
/**
 * Inline key array.
 * @param $buttons
 * @param $columns
 * @return array
 */
function inline_key_array($buttons, $columns)
{
    $result = array();
    $col = 0;
    $row = 0;

    foreach ($buttons as $v) {
        $result[$row][$col] = $v;
        $col++;

        if ($col >= $columns) {
            $row++;
            $col = 0;
        }
    }
    return $result;
}


/**
 * Universal key.
 * @param $keys
 * @param $id
 * @param $action
 * @param $arg
 * @param $text
 * @return array
 */
function universal_inner_key($keys, $id, $action, $arg, $text = '0')
{
    $keys = array(
                'text'          => $text,
                'callback_data' => $id . ':' . $action . ':' . $arg
            );

    // Write to log.
    //debug_log($keys);

    return $keys;
}

/**
 * Universal key.
 * @param $keys
 * @param $id
 * @param $action
 * @param $arg
 * @param $text
 * @return array
 */
function universal_key($keys, $id, $action, $arg, $text = '0')
{
    $keys[] = [
            array(
                'text'          => $text,
                'callback_data' => $id . ':' . $action . ':' . $arg
            )
        ];

    // Write to log.
    //debug_log($keys);

    return $keys;
}


/**
 * Share keys.
 * @param $id
 * @param $action
 * @param $update
 * @param $chats
 * @param $prefix_text
 * @param $hide
 * @return array
 */
function share_keys($id, $action, $update, $chats = '', $prefix_text = '', $hide = false, $level = '')
{
    global $config;
    // Check access.
    $share_access = bot_access_check($update, 'share-any-chat', true);

    // Add share button if not restricted to allow sharing to any chat.
    if ($share_access == true && $hide == false) {
        debug_log('Adding general share key to inline keys');
        // Set the keys.
        $keys[] = [
            [
                'text'                => getTranslation('share'),
                'switch_inline_query' => basename(ROOT_PATH) . ':' . strval($id)
            ]
        ];
    }

    // Add buttons for predefined sharing chats.
    // Default SHARE_CHATS or special chat list via $chats?
    if(!empty($chats)) {
        debug_log($chats, 'Got specific chats to share to:');
        $chats = explode(',', $chats);
    } else {
        if(!empty($level)) {
            // find chats to share ourselves, if we can
            debug_log($level, 'Did not get specific chats to share to, checking level specific for: ');
            $level_chat = 'SHARE_CHATS_LEVEL_' . $level;
            if(!empty($config->{$level_chat})) {
                $chats = explode(',', $config->{$level_chat});
                debug_log($chats, 'Found level specific chats to share to: ');
            } else {
            $chats = explode(',', $config->SHARE_CHATS);
            debug_log($chats, 'Chats not specified for level, sharing to globals: ');
        }
        } else {
            $chats = explode(',', $config->SHARE_CHATS);
            debug_log($chats, 'Level not given, sharing to globals: ');
        }
    }
    // Add keys for each chat.
    if(!empty($chats)){
        foreach($chats as $chat) {
            // Get chat object
            debug_log("Getting chat object for '" . $chat . "'");
            $chat_obj = get_chat($chat);

            // Check chat object for proper response.
            if ($chat_obj['ok'] == true) {
                debug_log('Proper chat object received, continuing to add key for this chat: ' . $chat_obj['result']['title']);
                $keys[] = [
                    [
                        'text'          => $prefix_text . getTranslation('share_with') . ' ' . $chat_obj['result']['title'],
                        'callback_data' => $id . ':' . $action . ':' . $chat
                    ]
                ];
            }
        }
    } else {
      debug_log('Aint got any chats to share to!');
    }

    return $keys;
}

?>
