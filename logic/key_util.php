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
 * Share keys. Has own logic for fetching chat id's if used to generate share keys for raids.
 * @param int $id Id to pass to callback query
 * @param string $action Action to pass to callback query
 * @param array $update Update from Telegram
 * @param int $raidLevel Raid level if sharing a raid
 * @param string $chats List of chats if using alternative list
 * @param bool $hideGeneralShare Leave out the general share button
 * @return array
 */
function share_keys($id, $action, $update, $raidLevel = '', $chats = '', $hideGeneralShare = false)
{
    global $config, $botUser;
    $keys = [];
    // Check access.
    $share_access = $botUser->accessCheck('share-any-chat', true);

    // Add share button if not restricted to allow sharing to any chat.
    if ($share_access == true && $hideGeneralShare == false) {
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
        if(!empty($raidLevel)) {
            // find chats to share ourselves, if we can
            debug_log($raidLevel, 'Did not get specific chats to share to, checking level specific for: ');
            $level_chat = 'SHARE_CHATS_LEVEL_' . $raidLevel;
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
        if($raidLevel == '') {
            // If raid level is not set we are sharing something else than a raid.
            $sharedChats = [];
        } else {
            $queryShared = my_query("
                    SELECT DISTINCT  chat_id
                    FROM    cleanup
                    WHERE   raid_id=?",
                    [ $id ]
                );
            $sharedChats = $queryShared->fetchAll(PDO::FETCH_COLUMN, 0);
        }
        foreach($chats as $chat) {
            if(in_array($chat, $sharedChats)) continue;
            // Get chat object
            debug_log("Getting chat object for '" . $chat . "'");
            $chat_obj = get_chat($chat);

            // Check chat object for proper response.
            if ($chat_obj['ok'] == true) {
                debug_log('Proper chat object received, continuing to add key for this chat: ' . $chat_obj['result']['title']);
                $keys[] = [
                    [
                        'text'          => getTranslation('share_with') . ' ' . $chat_obj['result']['title'],
                        'callback_data' => $id . ':' . $action . ':' . $chat
                    ]
                ];
            } else {
                info_log($chat, 'Invalid chat id in your configuration:');
            }
        }
    } else {
      debug_log('Aint got any chats to share to!');
    }

    return $keys;
}

/**
 * Format
 * @param array $array
 * @return string Formated
 */
function formatCallbackData($array)
{
  $return = $array['callbackAction'] . '|';
  unset($array['callbackAction']);
  foreach($array as $key => $value) {
    $return .= $key . '=' . $value . '|';
  }
  return rtrim($return, '|');
}
