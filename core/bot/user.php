<?php

class botUser
{
  public $userPrivileges = [
    'privileges' => [],
    'grantedBy' => '',
  ];

  /**
   * Run privilege check for Telegram user and save them for later use.
   * @param $update Update array from Telegram
  */
  public function privilegeCheck($update) {
    global $config;
    // Get Telegram user ID to check access from $update - either message, callback_query or inline_query
    $update_type = '';
    $update_type = !empty($update['message']['from']['id']) ? 'message' : $update_type;
    $update_type = (empty($update_type) && !empty($update['callback_query']['from']['id'])) ? 'callback_query' : $update_type;
    $update_type = (empty($update_type) && !empty($update['inline_query']['from']['id'])) ? 'inline_query' : $update_type;
    if(empty($update_type)) return; // If no update type was found, the call probably didn't come from Telegram and privileges don't need to be checked
    $user_id = $update[$update_type]['from']['id'];

    // Write to log.
    debug_log('Telegram message type: ' . $update_type);
    debug_log('Checking access for ID: ' . $user_id);

    // Public access?
    if(empty($config->BOT_ADMINS)) {
      debug_log('Bot access is not restricted! Allowing access for user: ' . CR . $user_id);
      $this->userPrivileges['grantedBy'] = 'NOT_RESTRICTED';
      return;
    }else {
      // Admin?
      $admins = explode(',', $config->BOT_ADMINS);
      if(in_array($user_id,$admins)) {
        debug_log('Positive result on access check for Bot Admins');
        debug_log('Bot Admins: ' . $config->BOT_ADMINS);
        debug_log('user_id: ' . $user_id);
        $this->userPrivileges['grantedBy'] = 'BOT_ADMINS';
        return;
      }
    }

    $telegramRoles = ['creator', 'admins', 'members', 'restricted', 'kicked'];

    // If user specific permissions are found, use them instead of group based
    if (is_file(ACCESS_PATH . '/access' . $user_id)) {
      $accessChats = [$user_id => null];
    }else {
      $chatIds = [];
      foreach($telegramRoles as $roleToCheck) {
        $chatFiles = str_replace(ACCESS_PATH . '/' . $roleToCheck, '', glob(ACCESS_PATH . '/' . $roleToCheck . '-*'));
        $chatIds = array_merge($chatIds, $chatFiles);
      }
      // Delete duplicates
      $chatsToCheck = array_unique($chatIds);
      $tg_json = [];
      // Check access and permission
      foreach($chatsToCheck as $chat) {
        // Get chat object - remove comments from filename
        // This way some kind of comment like the channel name can be added to the end of the filename, e.g. creator-100123456789-MyPokemonChannel to easily differ between access files :)
        // Source: php.net/manual/en/function.intval.php#7707
        preg_match_all('/-?\d+/', $chat, $tg_chat);
        $tg_chat = $tg_chat[0][0];
        debug_log("Getting chat object for '$tg_chat'");

        // Group/channel?
        if($tg_chat[0] == '-') {
          // Get chat member object and check status
          debug_log("Getting user from chat '$tg_chat'");
          $tg_json[$tg_chat] = get_chatmember($tg_chat, $user_id, true);
        }
      }
      $accessChats = curl_json_multi_request($tg_json);
    }
    // Loop through different chats
    foreach($accessChats as $chatId => $chatObj) {
      // Object contains response from Telegram
      if(isset($chatObj['ok'])) {
        if($chatObj['ok'] == true) {
          debug_log('Proper chat object received, continuing with access check.');

          // Get access file based on user status/role.
          debug_log('Role of user ' . $chatObj['result']['user']['id'] . ' : ' . $chatObj['result']['status']);

          if(in_array($chatObj['result']['status'], $telegramRoles) && is_file(ACCESS_PATH . '/' . $chatObj['result']['status'] . $chatId)) {
            $privilegeList = file(ACCESS_PATH . '/' . $chatObj['result']['status'] . $chatId, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $accessFile = $chatObj['result']['status'] . $chatId;

          // Any other user status/role except "left"
          } else if($chatObj['result']['status'] != 'left' && is_file(ACCESS_PATH . '/access' . $chatId)) {
            $privilegeList = file(ACCESS_PATH . '/access' . $chatId, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $accessFile = 'access' . $chatId;

            // Ignore "Restricted"?
            if($chatObj['result']['status'] == 'restricted' && in_array('ignore-restricted', $privilegeList)) {
              // Reset access file.
              $privilegeList = NULL;
            }

            // Ignore "kicked"?
            if($chatObj['result']['status'] == 'kicked' && in_array('ignore-kicked', $privilegeList)) {
              // Reset access file.
              $privilegeList = NULL;
            }
          }

          // Debug.
          debug_log('Access file:');
          debug_log($privilegeList);
        } else {
          // Invalid chat
          debug_log('Chat ' . $chatId . ' does not exist! Continuing with next chat...');
          continue;
        }
      // Process user specific access file
      }else {
        if(is_file(ACCESS_PATH . '/access' . $chatId)) {
          $privilegeList = file(ACCESS_PATH . '/access' . $chatId, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
          $accessFile = 'access' . $chatId;
        }
      }

      // Save privileges if found
      if(is_array($privilegeList)) {
        debug_log($accessFile, 'Positive result on access check in file:');
        $this->userPrivileges = [
          'privileges' => $privilegeList,
          'grantedBy' => $accessFile,
        ];
        break;
      }
      // Deny access
      debug_log($chat, 'Negative result on access check for chat:');
      debug_log('Continuing with next chat...');
    }
  }

  /**
   * Check users privileges for a specific action. Exits by default if access is denied.
   * @param $update Update array from Telegram
   * @param $permission Permission to check
   * @param $return_result Return the result of privilege check
   * @param $new_user Has user completed tutorial or not
   * @return bool|string
  */
  public function accessCheck($update, $permission = 'access-bot', $return_result = false, $new_user = false) {
    if(in_array($permission, $this->userPrivileges['privileges']) or $this->userPrivileges['grantedBy'] === 'BOT_ADMINS' or $this->userPrivileges['grantedBy'] === 'NOT_RESTRICTED') {
      // If a config file matching users status was found, check if tutorial is forced
      if($new_user && (in_array("force-tutorial", $this->userPrivileges['privileges']) or $this->userPrivileges['grantedBy'] === 'BOT_ADMINS' or $this->userPrivileges['grantedBy'] === 'NOT_RESTRICTED')) {
        return false;
      }
      return true;
    }else {
      debug_log('Denying access to the bot for user');

      if($return_result)
        return false;

      $response_msg = '<b>' . getTranslation('bot_access_denied') . '</b>';
      // Edit message or send new message based on type of received call
      if (isset($update['callback_query'])) {
        $keys = [];

        // Telegram JSON array.
        $tg_json = array();
        $tg_json[] = edit_message($update, $response_msg, $keys, false, true);
        $tg_json[] = answerCallbackQuery($update['callback_query']['id'], getTranslation('bot_access_denied'), true);

        curl_json_multi_request($tg_json);
      } else {
        send_message($update['message']['from']['id'], $response_msg);
      }
      exit;
    }
  }
}
?>