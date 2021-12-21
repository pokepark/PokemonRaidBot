<?php

// For tutorial mode: Prevent new users from using any bot keys except tutorial
if($config->TUTORIAL_MODE && isset($update['callback_query']['from']['id']) && new_user($update['callback_query']['from']['id']) && $data['action'] != "tutorial") {
    answerCallbackQuery($update['callback_query']['id'],  getTranslation("tutorial_vote_failed"));
    $dbh = null;
    exit();
}

// Set module path by sent action name.
$module = ROOT_PATH . '/mods/' . basename($data['action']) . '.php';

// Write module to log.
debug_log($module);

// Check if the module file exists.
if (file_exists($module)) {
    // Dynamically include module file and exit.
    include_once($module);
    exit();

// Module file is missing.
} else {
    // Write to log.
    debug_log('No action');
}
