<?php
/**
 * Bot access check.
 * @param $update
 * @param $permission
 * @param $return_result
 * @param $return_access
 * @return bool (if requested)
 */
function bot_access_check($update, $permission = 'access-bot', $return_result = false, $return_access = false)
{
    global $config;
    // Start with deny access
    $allow_access = false;

    // Get telegram ID to check access from $update - either message, callback_query or inline_query
    $update_type = '';
    $update_type = !empty($update['message']['from']['id']) ? 'message' : $update_type;
    $update_type = (empty($update_type) && !empty($update['callback_query']['from']['id'])) ? 'callback_query' : $update_type;
    $update_type = (empty($update_type) && !empty($update['inline_query']['from']['id'])) ? 'inline_query' : $update_type;
    $update_id = $update[$update_type]['from']['id'];

    // Write to log.
    debug_log('Telegram message type: ' . $update_type);
    debug_log('Checking access for ID: ' . $update_id);
    debug_log('Checking permission: ' . $permission);

    // Get all chat files for groups/channels like -100111222333
    // Creators
    $creator_chats = array();
    $creator_chats = str_replace(ACCESS_PATH . '/creator','',glob(ACCESS_PATH . '/creator-*'));

    // Admins
    $admin_chats = array();
    $admin_chats = str_replace(ACCESS_PATH . '/admins','',glob(ACCESS_PATH . '/admins-*'));

    // Members
    $member_chats = array();
    $member_chats = str_replace(ACCESS_PATH . '/members','',glob(ACCESS_PATH . '/members-*'));

    // Restricted
    $restricted_chats = array();
    $restricted_chats = str_replace(ACCESS_PATH . '/restricted','',glob(ACCESS_PATH . '/restricted-*'));

    // Kicked
    $kicked_chats = array();
    $kicked_chats = str_replace(ACCESS_PATH . '/members','',glob(ACCESS_PATH . '/kicked-*'));

    // Access chats
    $access_chats = array();
    $access_chats = str_replace(ACCESS_PATH . '/access','',glob(ACCESS_PATH . '/access-*'));
    $access_chats = array_merge($access_chats, $creator_chats, $admin_chats, $member_chats, $restricted_chats, $kicked_chats);

    // Add Admins if a group/channel
    if(!empty($config->BOT_ADMINS)) {
        $bot_admins = explode(',',$config->BOT_ADMINS);
        foreach($bot_admins as $admin) {
            // Ignore individuals, add only groups/channels
            if($admin[0] == '-') {
                array_unshift($access_chats,$admin);
            }
        }
    }
    // Add update_id
    if (is_file(ACCESS_PATH . '/access' . $update_id)) {
        $access_chats[] = $update_id;
    }
    // Delete duplicates
    $access_chats = array_unique($access_chats);

    // Get count of access files.
    $access_count = count($access_chats);

    // Check each chat
    debug_log('Checking these chats:');
    debug_log($access_chats);
    debug_log($access_count, 'Chat count:');

    // Record why access was granted
    $access_granted_by = false;

    // Make sure we checked the BOT_ADMINS
    $admins_checked = false;

    // Check access files, otherwise check only BOT_ADMINS as no access files are existing yet.
    if($access_count > 0) {
        // Check access and permission
        foreach($access_chats as $chat) {
            // Get chat object - remove comments from filename
            // This way some kind of comment like the channel name can be added to the end of the filename, e.g. creator-100123456789-MyPokemonChannel to easily differ between access files :)
            // Source: php.net/manual/en/function.intval.php#7707
            preg_match_all('/-?\d+/', $chat, $tg_chat);
            $tg_chat=$tg_chat[0][0];
            debug_log("Getting chat object for '$tg_chat'");
            $chat_object = get_chat($tg_chat);

            // Check chat object for proper response.
            if($chat_object['ok'] == true) {
                debug_log('Proper chat object received, continuing with access check.');
            } else {
                debug_log('Chat ' . $chat . ' does not exist! Continuing with next chat...');
                continue;
            }

            // Group/channel?
            if($chat[0] == '-') {
               // Get chat member object and check status
               debug_log("Getting user from chat '$tg_chat'");
               $chat_obj = get_chatmember($tg_chat, $update_id);

               // Make sure we get a proper response
               if($chat_obj['ok'] == true) {
                    debug_log('Proper chat object received, continuing with access check.');

                    // Admin?
                    $admins = explode(',', $config->BOT_ADMINS);
                    if(in_array($tg_chat,$admins) || in_array($update_id,$admins)) {
                        debug_log('Positive result on access check for Bot Admins');
                        debug_log('Bot Admins: ' . $config->BOT_ADMINS);
                        debug_log('chat: ' . $tg_chat);
                        debug_log('update_id: ' . $update_id);
                        $admins_checked = true;
                        $allow_access = true;
                        $access_granted_by = 'BOT_ADMINS';
                        break;
                    } else {
                        // Get access file based on user status/role.
                        debug_log('Role of user ' . $chat_obj['result']['user']['id'] . ' : ' . $chat_obj['result']['status']);

                        // Creator
                        if($chat_obj['result']['status'] == 'creator' && is_file(ROOT_PATH . '/access/creator' . $chat)) {
                            $access_file = file(ROOT_PATH . '/access/creator' . $chat, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                            $afile = 'creator' . $chat;

                        // Admin
                        } else if($chat_obj['result']['status'] == 'administrator' && is_file(ROOT_PATH . '/access/admins' . $chat)) {
                            $access_file = file(ROOT_PATH . '/access/admins' . $chat, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                            $afile = 'admins' . $chat;

                        // Member
                        } else if($chat_obj['result']['status'] == 'member' && is_file(ROOT_PATH . '/access/members' . $chat)) {
                            $access_file = file(ROOT_PATH . '/access/members' . $chat, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                            $afile = 'members' . $chat;

                        // Restricted
                        } else if($chat_obj['result']['status'] == 'restricted' && is_file(ROOT_PATH . '/access/restricted' . $chat)) {
                            $access_file = file(ROOT_PATH . '/access/restricted' . $chat, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                            $afile = 'restricted' . $chat;

                        // Kicked
                        } else if($chat_obj['result']['status'] == 'kicked' && is_file(ROOT_PATH . '/access/kicked' . $chat)) {
                            $access_file = file(ROOT_PATH . '/access/kicked' . $chat, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                            $afile = 'kicked' . $chat;

                        // Any other user status/role except "left"
                        } else if($chat_obj['result']['status'] != 'left' && is_file(ROOT_PATH . '/access/access' . $chat)) {
                            $access_file = file(ROOT_PATH . '/access/access' . $chat, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                            $afile = 'access' . $chat;

                            // Ignore "Restricted"?
                            if($chat_obj['result']['status'] == 'restricted' && in_array('ignore-restricted', $access_file)) {
                                // Reset access file.
                                $access_file = NULL;
                            }

                            // Ignore "kicked"?
                            if($chat_obj['result']['status'] == 'kicked' && in_array('ignore-kicked', $access_file)) {
                                // Reset access file.
                                $access_file = NULL;
                            }
                        }

                        // Debug.
                        debug_log('Access file:');
                        debug_log($access_file);

                        // Check user status/role and permission to access the function
                        if($chat_obj['result']['user']['id'] == $update_id && isset($access_file) && in_array($permission,$access_file)) {
                            debug_log($afile, 'Positive result on access check in file:');
                            debug_log($chat_object['result']['title'], 'Positive result on access check from chat:');
                            $allow_access = true;
                            $access_granted_by = $afile;
                            break;
                        } else {
                            // Deny access
                            debug_log($afile, 'Negative result on access check in file:');
                            debug_log('Continuing with next chat...');
                            continue;
                        }
                    }
                } else {
                    // Invalid chat
                    debug_log('Chat ' . $tg_chat . ' does not exist! Continuing with next chat...');
                    continue;
                }
            // Private chat
            } else {
                // Get chat object
                debug_log("Getting chat object for '$tg_chat'");
                $chat_obj = get_chat($tg_chat);

                // Check chat object for proper response.
                if($chat_obj['ok'] == true) {
                    debug_log('Proper chat object received, continuing with access check.');

                    // Admin?
                    $admins = explode(',',$config->BOT_ADMINS);
                    if(in_array($tg_chat,$admins) || in_array($update_id,$admins)) {
                        debug_log('Positive result on access check for Bot Admins');
                        $admins_checked = true;
                        $allow_access = true;
                        $access_granted_by = 'BOT_ADMINS';
                        break;
                    } else {
                        // Get access file
                        if(is_file(ROOT_PATH . '/access/access' . $chat)) {
                            $access_file = file(ROOT_PATH . '/access/access' . $chat, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                        }
                        $afile = 'access' . $chat;

                        // ID matching $chat, private chat type and permission to access the function
                        if($chat_obj['result']['id'] == $update_id && $chat_obj['result']['type'] == 'private' && isset($access_file) && in_array($permission,$access_file)) {
                            debug_log($afile, 'Positive result on access check in file:');
                            debug_log($chat_object['result']['first_name'], 'Positive result on access check for user:');
                            $allow_access = true;
                            $access_granted_by = $afile;
                            break;
                        } else if($chat_obj['result']['type'] == 'private') {
                            // Result was ok, but access not granted. Continue with next chat if type is private.
                            debug_log($afile, 'Negative result on access check in file:');
                            debug_log('Continuing with next chat...');
                            continue;
                        }
                    }
                } else {
                    // Invalid chat
                    debug_log('Chat ' . $tg_chat . ' does not exist! Continuing with next chat...');
                    continue;
                }
            }
        }

        // Check BOT_ADMINS if not checked already and not access was granted yet.
        if($admins_checked == false && $allow_access == false) {
            // Get chat object
            debug_log("Getting chat object for '" . $update_id . "'");
            $chat_user = get_chat($update_id);

            // Check chat object for proper response.
            if($chat_user['ok'] == true) {
                debug_log('Proper chat object received, continuing with access check.');
                // Admin?
                $admins = explode(',',$config->BOT_ADMINS);
                if(in_array($update_id,$admins)) {
                    debug_log('Positive result on access check for Bot Admins');
                    $allow_access = true;
                    $access_granted_by = 'BOT_ADMINS';
                } else {
                    debug_log('Negative result on access check for Bot Admins for user with ID: ' . $update_id);
                }
            } else {
                info_log('Error! Chat ' . $update_id . ' does not exist!');
            }
        }

    // Check BOT_ADMINS in case no access files are existing
    } else {
        // Get chat object
        debug_log("Getting chat object for '" . $update_id . "'");
        $chat_user = get_chat($update_id);

        // Check chat object for proper response.
        if($chat_user['ok'] == true) {
            debug_log('Proper chat object received, continuing with access check.');
            // Admin?
            $admins = explode(',',$config->BOT_ADMINS);
            if(in_array($update_id,$admins)) {
                debug_log('Positive result on access check for Bot Admins');
                $allow_access = true;
                $access_granted_by = 'BOT_ADMINS';
            } else {
                debug_log('Negative result on access check for Bot Admins for user with ID: ' . $update_id);
            }
        } else {
            info_log('Error! Chat ' . $update_id . ' does not exist!');
        }
    }

    // Result of access check?
    // Prepare logging of id, username and/or first_name
    $msg = '';
    $msg .= !empty($update[$update_type]['from']['id']) ? 'Id: ' . $update[$update_type]['from']['id']  . CR : '';
    $msg .= !empty($update[$update_type]['from']['username']) ? 'Username: ' . $update[$update_type]['from']['username'] . CR : '';
    $msg .= !empty($update[$update_type]['from']['first_name']) ? 'First Name: ' . $update[$update_type]['from']['first_name'] . CR : '';

    // Public access?
    if(empty($config->BOT_ADMINS)) {
        debug_log('Bot access is not restricted! Allowing access for user: ' . CR . $msg);
        $allow_access = true;
    }

    // Allow or deny access to the bot and log result
    if ($allow_access && !$return_result) {
        debug_log('Allowing access to the bot for user:' . CR . $msg);
        // Return access (BOT_ADMINS or access_file)
        if($return_access) {
            return $access_granted_by;
        }
    } else if ($allow_access && $return_result) {
        debug_log('Allowing access to the bot for user:' . CR . $msg);
        return $allow_access;
    } else if (!$allow_access && $return_result) {
        debug_log('Denying access to the bot for user:' . CR . $msg);
        return $allow_access;
    } else {
        debug_log('Denying access to the bot for user:' . CR . $msg);
        $response_msg = '<b>' . getTranslation('bot_access_denied') . '</b>';
        // Edit message or send new message based on value of $update_type
        if ($update_type == 'callback_query') {
            // Empty keys.
            $keys = [];

            // Telegram JSON array.
            $tg_json = array();

            // Edit message.
            $tg_json[] = edit_message($update, $response_msg, $keys, false, true);

            // Answer the callback.
            $tg_json[] = answerCallbackQuery($update[$update_type]['id'], getTranslation('bot_access_denied'), true);

            // Telegram multicurl request.
            curl_json_multi_request($tg_json);
        } else {
            sendMessage($update[$update_type]['from']['id'], $response_msg);
        }
        exit;
    }
}


?>
