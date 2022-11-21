<?php

// Include requirements and perfom initial steps
include_once(__DIR__ . '/core/bot/requirements.php');

if ($metrics){
  $requests_total->inc(['/']);
}

$botUser = new botUser;

// Start logging.
debug_log("RAID-BOT '" . $config->BOT_ID . "'");

// Check API Key and get input from telegram / webhook
include_once(CORE_BOT_PATH . '/apikey.php');
$update = get_verified_update(); // This also sets API_KEY

// We maybe receive a webhook so far...
if ($update){
  foreach ($update as $raid) {
    if (isset($raid['type']) && $raid['type'] == 'raid') {
      // Create raid(s) and exit.
      include_once(ROOT_PATH . '/commands/raid_from_webhook.php');
      exit();
    }
  }
}

$update['type'] = NULL;
if(isset($update['message'])) {
  $update['type'] = 'message';
} else if(isset($update['callback_query'])) {
  $update['type'] = 'callback_query';
} else if(isset($update['inline_query'])) {
  $update['type'] = 'inline_query';
} else if(isset($update['channel_post'])) {
  $update['type'] = 'channel_post';
}

// Init empty data array.
$data = [];

// Callback data found.
if (isset($update['callback_query']['data'])) {
  $thedata = $update['callback_query']['data'];
  $splitDataOld = explode(':', $thedata);
  // Split callback data and assign to data array.
  if(count($splitDataOld) > 2) {
    $data['id'] = $splitDataOld[0];
    $data['callbackAction'] = $splitDataOld[1];
    $data['arg'] = $splitDataOld[2];
  }else {
    $splitData = explode('|', $thedata);
    $data['callbackAction'] = $splitData[0];
    unset($splitData[0]);
    $data = [];
    foreach($splitData as $dataPiece) {
      [$key, $value] = explode('=', $dataPiece, 2);
      $data[$key] = $value;
    }
  }
}

// Run cleanup if requested
if (isset($update['cleanup'])) {
  include_once(CORE_BOT_PATH . '/cleanup_run.php');
  cleanup_auth_and_run($update);
  exit;
}

// DDOS protection
if($config->ENABLE_DDOS_PROTECTION) {
  include_once(CORE_BOT_PATH . '/ddos.php');
}

if(isset($update[$update['type']]['from'])) {
// Update the user
  $botUser->updateUser($update);

  // Get language
  $botUser->defineUserLanguage($update);

  $botUser->initPrivileges($update);
}

// Callback query received.
if (isset($update['callback_query'])) {
  // Logic to get the module
  include_once(CORE_BOT_PATH . '/modules.php');

// Inline query received.
} else if (isset($update['inline_query'])) {
  include_once(LOGIC_PATH . '/raid_list.php');
  // List raids and exit.
  raid_list($update);
  exit();

// Location received.
} else if (isset($update['message']['location']) && $update['message']['chat']['type'] == 'private') {
  if($config->RAID_VIA_LOCATION_FUNCTION == 'list') {
    include_once(ROOT_PATH . '/mods/share_raid_by_location.php');
  }else {
    // Create raid and exit.
    include_once(ROOT_PATH . '/mods/raid_by_location.php');
  }
  exit();

// Cleanup collection from channel/supergroup messages.
} else if (isset($update[$update['type']]) && in_array($update[$update['type']]['chat']['type'], ['channel', 'supergroup'])) {
  // Collect cleanup information
  include_once(CORE_BOT_PATH . '/cleanup_collect.php');

// Message is required to check for commands.
} else if (isset($update['message']) && $update['message']['chat']['type'] == 'private') {
  $botUser->privilegeCheck($update);
  // Portal message?
  if(isset($update['message']['entities']['1']['type']) && $update['message']['entities']['1']['type'] == 'text_link' && strpos($update['message']['entities']['1']['url'], 'https://intel.ingress.com/intel?ll=') === 0) {
    // Import portal.
    include_once(ROOT_PATH . '/mods/importal.php');
    exit;
  }
  // Check if user is expected to be posting something we want to save to db
  if($update['message']['chat']['type'] == 'private') {
    $q = my_query('SELECT id, handler, modifiers FROM user_input WHERE user_id = ? LIMIT 1', [$update['message']['from']['id']]);
    if( $q->rowCount() > 0 ) {
      debug_log('Expecting a response message from user: ' . $update['message']['from']['id']);
      $res = $q->fetch();
      // Modifiers to pass to handler
      $modifiers = json_decode($res['modifiers'], true);

      debug_log('Calling: ' . $res['handler'] . '.php');
      debug_log('With modifiers: ' . $res['modifiers']);
      include_once(ROOT_PATH . '/mods/' . $res['handler'] . '.php');

      debug_log('Response handeled successfully!');
      // Delete the entry if the call was handled without errors
      my_query('DELETE FROM user_input WHERE id = ?', [$res['id']]);

      exit();
    }
  }

  // Logic to get the command
  include_once(CORE_BOT_PATH . '/commands.php');
}
