<?php
// Check access.
$access = $botUser->accessCheck($update, 'help', true);

// Display help for each permission
if($access) {
  if($botUser->userPrivileges['grantedBy'] == 'BOT_ADMINS') {
    $permissions = array();
    $permissions[] = 'access-bot';
    $permissions[] = 'create';
    $permissions[] = 'ex-raids';
    $permissions[] = 'raid-duration';
    $permissions[] = 'list';
    $permissions[] = 'listall';
    $permissions[] = 'overview';
    $permissions[] = 'delete-all';
    $permissions[] = 'pokemon-all';
    $permissions[] = 'trainer';
    $permissions[] = 'gym-details';
    $permissions[] = 'gym-edit';
    $permissions[] = 'gym-add';
    $permissions[] = 'portal-import';
    $permissions[] = 'config-get';
    $permissions[] = 'config-set';
    $permissions[] = 'pokedex';
    $permissions[] = 'history';
    $permissions[] = 'event-manage';
    $permissions[] = 'help';
  } else {
    // Get permissions.
    $permissions = $botUser->userPrivileges['privileges'];
  }

  // Write to log.
  debug_log($permissions,'PERMISSIONS: ');

  // Show help header.
  debug_log('Showing help to user now');
  $msg = '<b>' . getTranslation('personal_help') . '</b>' . CR . CR;

  // Raid via location?
  if($config->RAID_VIA_LOCATION) {
    $msg .= EMOJI_CLIPPY . SP . getTranslation('help_create_via_location') . CR . CR;
  }

  // Show help.
  foreach($permissions as $id => $p) {
    if($p == 'access-bot' || strpos($p, 'share-') === 0 || strpos($p, 'ignore-') === 0) continue;
    if(getTranslation('help_' . $p)) {
      $msg .= getTranslation('help_' . $p) . CR . CR;
    }
  }
} elseif($config->TUTORIAL_MODE) {
  if(new_user($update['message']['from']['id'])) {
    $msg = $tutorial[0]['msg_new'];
  }else {
    $msg = $tutorial[0]['msg'];
  }
  $keys = [
  [
    [
      'text'          => getTranslation('next'),
      'callback_data' => '0:tutorial:0'
    ]
  ]
  ];
  $photo = $tutorial[0]['photo'];
  send_photo($update['message']['from']['id'],$photo, false, $msg, $keys, ['disable_web_page_preview' => 'true']);
  exit();

// No help for the user.
} else {
  $msg = getTranslation('bot_access_denied');
}

// Send message.
send_message($update['message']['from']['id'], $msg);
