<?php

// For tutorial mode: Prevent new users from using any bot keys except tutorial
if(isset($update['callback_query']['from']['id']) && new_user($update['callback_query']['from']['id']) && $data['callbackAction'] != 'tutorial') {
  answerCallbackQuery($update['callback_query']['id'],  getTranslation("tutorial_vote_failed"));
  exit();
}

// Set module path by sent action name.
$module = ROOT_PATH . '/mods/' . basename($data['callbackAction']) . '.php';

// Write module to log.
debug_log($module);

// Check if the module file exists.
if (file_exists($module)) {
  // Dynamically include module file and exit.
  include_once($module);
  exit();

}
// Module file is missing.
// Write to log.
debug_log('No action');
