<?php
// Init command.
$command = NULL;

// Check message text for a leading slash.
if (isset($update['message']['text']) && substr($update['message']['text'], 0, 1) == '/') {
  // Get command name.
  if($config->BOT_NAME) {
    $com = strtolower(str_replace('/', '', str_replace($config->BOT_NAME, '', explode(' ', $update['message']['text'])[0])));
  } else {
    info_log('BOT_NAME is missing! Please define it!', '!');
    $com = 'start';
  }

  if(isset($update['message']['chat']['id']) && new_user($update['message']['chat']['id']) && $com != 'start' && $com != 'tutorial') {
    send_message($update['message']['chat']['id'],  getTranslation("tutorial_command_failed"));
    exit();
  }

  // Set command paths.
  $command = ROOT_PATH . '/commands/' . basename($com) . '.php';
  $startcommand = ROOT_PATH . '/commands/start.php';

  // Write to log.
  debug_log('Command-File: ' . $command);
  debug_log('Start Command-File: ' . $startcommand);

  // Check if command file exits.
  if (is_file($command)) {
    // Dynamically include command file and exit.
    include_once($command);
  } else if ($com == basename(ROOT_PATH)) {
    // Include start file and exit.
    include_once($startcommand);
  } else {
    send_message($update['message']['chat']['id'], '<b>' . getTranslation('not_supported') . '</b>');
  }
}
