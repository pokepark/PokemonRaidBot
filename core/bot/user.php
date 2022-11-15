<?php

class botUser
{
  public $userPrivileges = [
    'privileges' => [],
    'grantedBy' => '',
  ];
  public $userLanguage = '';

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

    // Map telegram roles to access file names
    $telegramRoles = [
        'creator'       => 'creator',
        'administrator' => 'admins',
        'member'        => 'members',
        'restricted'    => 'restricted',
        'kicked'        => 'kicked',
      ];

    $accessFilesList = $tg_json = $chatIds = [];
    foreach(glob(ACCESS_PATH . '/*') as $filePath) {
      $filename = str_replace(ACCESS_PATH . '/', '', $filePath);
      // Get chat object - remove comments from filename
      // This way some kind of comment like the channel name can be added to the end of the filename, e.g. creator-100123456789-MyPokemonChannel to easily differ between access files :)
      preg_match('/(access)('.$user_id.')|(access|creator|admins|members|restricted|kicked)(-[0-9]+)/', '-' . $filename, $result);
      if(empty($result[0])) continue;
      // User specific access file found?
      if(!empty($result[1])) {
        $privilegeList = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        debug_log($filename, 'Positive result on access check in file:');
        $this->userPrivileges = [
          'privileges' => $privilegeList,
          'grantedBy' => $filename,
        ];
        return;
      }
      // Group/channel?
      $role = $result[3];
      $tg_chat = $result[4];
      $chatIds[] = $tg_chat;
      // Save the full filename (with possible comments) to an array for later use
      $accessFilesList[$role.$tg_chat] = $filename;
      debug_log("Asking Telegram if user is a member of chat '$tg_chat'");
      if(!isset($tg_json[$tg_chat])) $tg_json[$tg_chat] = get_chatmember($tg_chat, $user_id, true); // Get chat member object and check status
    }
    $accessChats = curl_json_multi_request($tg_json);

    // Loop through different chats
    foreach($accessChats as $chatId => $chatObj) {
      $userStatus = $chatObj['result']['status'];
      if(!isset($chatObj['ok']) or $chatObj['ok'] != true or $userStatus == 'left' or !array_key_exists($userStatus, $telegramRoles)){
        // Deny access
        debug_log($chatId, 'Negative result on access check for chat:');
        debug_log('Continuing with next chat...');
        continue;
      }
      // Object contains response from Telegram
      debug_log('Proper chat object received, continuing with access check.');
      debug_log('Role of user ' . $chatObj['result']['user']['id'] . ' : ' . $chatObj['result']['status']);

      // Get access file based on user status/role.
      $roleAndChat = $telegramRoles[$userStatus] . $chatId;
      if(array_key_exists($roleAndChat, $accessFilesList)) {
        $privilegeList = file(ACCESS_PATH . '/' . $accessFilesList[$roleAndChat], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $accessFile = $accessFilesList[$roleAndChat];

      // Any other user status/role except "left"
      } else if(array_key_exists('access' . $chatId, $accessFilesList)) {
        $privilegeList = file(ACCESS_PATH . '/' . $accessFilesList['access' . $chatId], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $accessFile = $accessFilesList['access' . $chatId];

        // Ignore "Restricted" or "kicked"?
        if( ($userStatus == 'restricted' && in_array('ignore-restricted', $privilegeList))
         or ($userStatus == 'kicked' && in_array('ignore-kicked', $privilegeList))) {
          $privilegeList = NULL;
        }
        // Debug.
        debug_log('Access file:');
        debug_log($privilegeList);
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
    if(!$new_user && in_array($permission, $this->userPrivileges['privileges']) or $this->userPrivileges['grantedBy'] === 'BOT_ADMINS' or $this->userPrivileges['grantedBy'] === 'NOT_RESTRICTED') {
      return true;
    }
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

  /**
   * Raid access check.
   * @param $update
   * @param $data
   * @return bool
   */
  public function raidAccessCheck($update, $raidId, $permission, $return_result = false)
  {
    global $botUser;
    // Default: Deny access to raids
    $raid_access = false;

    // Build query.
    $rs = my_query(
        "
        SELECT    user_id
        FROM      raids
        WHERE     id = {$raidId}
        "
    );

    $raid = $rs->fetch();

    // Check permissions
    if ($rs->rowCount() == 0 or $update['callback_query']['from']['id'] != $raid['user_id']) {
      // Check "-all" permission
      debug_log('Checking permission:' . $permission . '-all');
      $permission = $permission . '-all';
      $raid_access = $botUser->accessCheck($update, $permission, $return_result);
    } else {
      // Check "-own" permission
      debug_log('Checking permission:' . $permission . '-own');
      $permission_own = $permission . '-own';
      $permission_all = $permission . '-all';
      $raid_access = $botUser->accessCheck($update, $permission_own, true);

      // Check "-all" permission if we get "access denied"
      // Maybe necessary if user has only "-all" configured, but not "-own"
      if(!$raid_access) {
        debug_log('Permission check for ' . $permission_own . ' failed! Maybe the access is just granted via ' . $permission . '-all ?');
        debug_log('Checking permission:' . $permission_all);
        $raid_access = $botUser->accessCheck($update, $permission_all, $return_result);
      } else {
        $raid_access = $botUser->accessCheck($update, $permission_own, $return_result);
      }
    }

    // Return result
    return $raid_access;
  }

  /**
   * Update users info if allowed
   * @param $update
   * @return bool|mysqli_result
  */
  public function updateUser($update)
  {
    global $ddos_count;

    // Check DDOS count
    if ($ddos_count < 2) {
      // Update the user.
      $userUpdate = $this->updateUserdb($update);

      // Write to log.
      debug_log('Update user: ' . $userUpdate);
    }
  }

  /**
   * Define userlanguage
   * @param $update
   * @return bool|mysqli_result
  */
  public function defineUserLanguage($update) {
    global $config;
    // Write to log.
    debug_log('Language Check');

    // Get language from user - otherwise use language from config.
    if ($config->LANGUAGE_PRIVATE == '') {
      // Message or callback?
      if(isset($update['message']['from'])) {
        $from = $update['message']['from'];
      } else if(isset($update['callback_query']['from'])) {
        $from = $update['callback_query']['from'];
      } else if(isset($update['inline_query']['from'])) {
        $from = $update['inline_query']['from'];
      }
      if(isset($from)) {
        $q = my_query("SELECT lang FROM users WHERE user_id='".$from['id']."' LIMIT 1");
        $res = $q->fetch();
        $language_code = $res['lang'];
      }else {
        $language_code = '';
      }

      // Get and define userlanguage.
      $languages = $GLOBALS['languages'];

      // Get languages from normal translation.
      if(array_key_exists($language_code, $languages)) {
        $userlanguage = $languages[$language_code];
      } else {
        $userlanguage = DEFAULT_LANGUAGE;
      }

      debug_log('User language: ' . $userlanguage);
      $this->userLanguage = $userlanguage;
    } else {
      // Set user language to language from config.
      $this->userLanguage = $config->LANGUAGE_PRIVATE;
    }
  }

  /**
  * Update users info into database.
  * @param $update
  * @return bool|mysqli_result
  */
  private function updateUserdb($update)
  {
    global $dbh, $config;

    if (isset($update['message']['from'])) {
      $msg = $update['message']['from'];
    } else if (isset($update['callback_query']['from'])) {
      $msg = $update['callback_query']['from'];
    } else if (isset($update['inline_query']['from'])) {
      $msg = $update['inline_query']['from'];
    }

    if (empty($msg['id'])) {
      debug_log('No id', '!');
      debug_log($update, '!');
      return false;
    }
    $id = $msg['id'];

    $name = '';
    $sep = '';

    if (isset($msg['first_name'])) {
      $name = $msg['first_name'];
      $sep = ' ';
    }

    if (isset($msg['last_name'])) {
      $name .= $sep . $msg['last_name'];
    }

    $nick = (isset($msg['username'])) ? $msg['username'] : '';

    $lang = (isset($msg['language_code']) && array_key_exists($msg['language_code'], $GLOBALS['languages'])) ? $msg['language_code'] : 'en';

    // Create or update the user.
    $stmt = $dbh->prepare(
      '
      INSERT INTO users
      SET         user_id = :id,
                  nick    = :nick,
                  name    = :name,
                  lang    = :lang,
                  auto_alarm = :auto_alarm
      ON DUPLICATE KEY
      UPDATE      nick    = :nick,
                  name    = :name,
                  lang    = IF(lang_manual = 1, lang, :lang),
                  auto_alarm = IF(:auto_alarm = 1, 1, auto_alarm)
      '
    );
    $alarm_setting = ($config->RAID_AUTOMATIC_ALARM ? 1 : 0);
    $stmt->bindParam(':id', $id);
    $stmt->bindParam(':nick', $nick);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':lang', $lang);
    $stmt->bindParam(':auto_alarm', $alarm_setting);
    $stmt->execute();

    return 'Updated user ' . $nick;
  }
}
?>