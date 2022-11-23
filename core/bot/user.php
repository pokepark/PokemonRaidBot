<?php

class botUser
{
  public $userPrivileges = [
    'privileges' => [],
    'grantedBy' => '',
  ];
  public $userLanguage = '';
  public $ddosCount = 0;
  public $userId = 0;

  /**
   * Read user privileges from db
   * @param array $update Update array from Telegram
  */
  public function initPrivileges() {
    $q = my_query('SELECT privileges FROM users WHERE user_id = ? LIMIT 1', [$this->userId]);
    $result = $q->fetch();
    if($result['privileges'] === NULL) return;
    $this->userPrivileges = json_decode($result['privileges'], true);
  }

  /**
   * Run privilege check for Telegram user and save them for later use.
   * @param array $update Update array from Telegram
  */
  public function privilegeCheck() {
    global $config;
    // Write to log.
    debug_log('Checking access for ID: ' . $this->userId);

    // Public access?
    if(empty($config->BOT_ADMINS)) {
      debug_log('Bot access is not restricted! Allowing access for user: ' . CR . $this->userId);
      $this->userPrivileges['grantedBy'] = 'NOT_RESTRICTED';
      return;
    }
    // Admin?
    $admins = explode(',', $config->BOT_ADMINS);
    if(in_array($this->userId, $admins)) {
      debug_log('Positive result on access check for Bot Admins');
      debug_log('Bot Admins: ' . $config->BOT_ADMINS);
      debug_log('user_id: ' . $this->userId);
      $this->userPrivileges['grantedBy'] = 'BOT_ADMINS';
      return;
    }

    // Map telegram roles to access file names
    $telegramRoles = [
      'creator'       => 'creator',
      'administrator' => 'admins',
      'member'        => 'members',
      'restricted'    => 'restricted',
      'kicked'        => 'kicked',
    ];

    $accessFilesList = $tg_json = [];
    foreach(glob(ACCESS_PATH . '/*') as $filePath) {
      $filename = str_replace(ACCESS_PATH . '/', '', $filePath);
      // Get chat object - remove comments from filename
      // This way some kind of comment like the channel name can be added to the end of the filename, e.g. creator-100123456789-MyPokemonChannel to easily differ between access files :)
      preg_match('/(access)('.$this->userId.')|(access|creator|admins|members|restricted|kicked)(-[0-9]+)/', '-' . $filename, $result);
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
      // Save the full filename (with possible comments) to an array for later use
      $accessFilesList[$role.$tg_chat] = $filename;
      debug_log('Asking Telegram if user is a member of chat \'' . $tg_chat . '\'');
      if(!isset($tg_json[$tg_chat])) $tg_json[$tg_chat] = get_chatmember($tg_chat, $this->userId, true); // Get chat member object and check status
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
      if(isset($privilegeList) && is_array($privilegeList)) {
        debug_log($accessFile, 'Positive result on access check in file:');
        $privilegeArray = [
          'privileges' => $privilegeList,
          'grantedBy' => $accessFile,
        ];
        my_query('UPDATE users SET privileges = ? WHERE user_id = ? LIMIT 1', [json_encode($privilegeArray), $this->userId]);
        $this->userPrivileges = $privilegeArray;
        break;
      }
      // Deny access
      debug_log($chatId, 'Negative result on access check for chat:');
      debug_log('Continuing with next chat...');
    }
  }

  /**
   * Check users privileges for a specific action. Exits by default if access is denied.
   * @param string $permission Permission to check
   * @param bool $return_result Return the result of privilege check
   * @param bool $new_user Has user completed tutorial or not
   * @return bool|string
  */
  public function accessCheck($permission = 'access-bot', $return_result = false, $new_user = false) {
    global $update;
    if(!$new_user && in_array($permission, $this->userPrivileges['privileges']) or $this->userPrivileges['grantedBy'] === 'BOT_ADMINS' or $this->userPrivileges['grantedBy'] === 'NOT_RESTRICTED') {
      return true;
    }
    debug_log('Denying access to the bot for user');

    if($return_result)
      return false;

    $response_msg = '<b>' . getTranslation('bot_access_denied') . '</b>';
    // Edit message or send new message based on type of received call
    if ($update['type'] != 'callback_query') {
      send_message($update['message']['from']['id'], $response_msg);
      exit;
    }
    $keys = [];

    // Telegram JSON array.
    $tg_json = array();
    $tg_json[] = edit_message($update, $response_msg, $keys, false, true);
    $tg_json[] = answerCallbackQuery($update['callback_query']['id'], getTranslation('bot_access_denied'), true);

    curl_json_multi_request($tg_json);
    exit;
  }

  /**
   * Raid access check.
   * @param int $raidId
   * @param string $permission
   * @param bool $return_result
   * @return bool
   */
  public function raidaccessCheck($raidId, $permission, $return_result = false)
  {
    global $update;
    // Default: Deny access to raids
    $raid_access = false;

    // Build query.
    $rs = my_query('
      SELECT    user_id
      FROM      raids
      WHERE     id = ?
      ', [$raidId]
    );

    $raid = $rs->fetch();

    // Check permissions
    if ($rs->rowCount() == 0 or $this->userId != $raid['user_id']) {
      // Check "-all" permission
      debug_log('Checking permission:' . $permission . '-all');
      $permission = $permission . '-all';
      return $this->accessCheck($permission, $return_result);
    }
    // Check "-own" permission
    debug_log('Checking permission:' . $permission . '-own');
    $permission_own = $permission . '-own';
    $permission_all = $permission . '-all';
    $raid_access = $this->accessCheck($permission_own, true);

    if($raid_access) {
      return $this->accessCheck($permission_own, $return_result);
    }
    // Check "-all" permission if we get "access denied"
    // Maybe necessary if user has only "-all" configured, but not "-own"
    debug_log('Permission check for ' . $permission_own . ' failed! Maybe the access is just granted via ' . $permission . '-all ?');
    debug_log('Checking permission:' . $permission_all);
    return $this->accessCheck($permission_all, $return_result);
  }

  /**
   * Update users info if allowed
   * @param $update
  */
  public function updateUser($update)
  {
    // Check DDOS count
    if ($this->ddosCount >= 2) return;
    // Update the user.
    $userUpdate = $this->updateUserdb($update);

    // Write to log.
    debug_log('Update user: ' . $userUpdate);
  }

  /**
   * Define userlanguage
   * @param $update
  */
  public function defineUserLanguage($update) {
    global $config;
    // Write to log.
    debug_log('Language Check');

    // Get language from user - otherwise use language from config.
    if ($config->LANGUAGE_PRIVATE != '') {
      // Set user language to language from config.
      $this->userLanguage = $config->LANGUAGE_PRIVATE;
      return;
    }
    // Message or callback?

    $language_code = '';
    $q = my_query('SELECT lang FROM users WHERE user_id = ?  LIMIT 1', [$this->userId]);
    $res = $q->fetch();
    $language_code = $res['lang'];

    // Get and define userlanguage.
    $languages = $GLOBALS['languages'];

    // Get languages from normal translation.
    $userlanguage = (array_key_exists($language_code, $languages)) ? $languages[$language_code] : DEFAULT_LANGUAGE;

    debug_log('User language: ' . $userlanguage);
    $this->userLanguage = $userlanguage;
  }

  /**
  * Update users info into database.
  * @param $update
  */
  private function updateUserdb($update)
  {
    global $config;

    $msg = $update[$update['type']]['from'];

    if (empty($msg['id'])) {
      debug_log('No id', '!');
      debug_log($update, '!');
      return false;
    }
    $id = $this->userId = $msg['id'];

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

    $alarm_setting = ($config->RAID_AUTOMATIC_ALARM ? 1 : 0);

    // Create or update the user.
    my_query('
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
      ',
      [
        ':id' => $id,
        ':nick' => $nick,
        ':name' => $name,
        ':lang' => $lang,
        ':auto_alarm' => $alarm_setting,
      ]
    );

    return 'Updated user ' . $nick;
  }
}
