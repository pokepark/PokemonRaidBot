<?php
// Init command.
$command = NULL;

// Check message text for a leading slash.
if (substr($update['message']['text'], 0, 1) == '/') {
    // Get command name.
    if($config->BOT_NAME) {
        $com = strtolower(str_replace('/', '', str_replace($config->BOT_NAME, '', explode(' ', $update['message']['text'])[0])));
        $altcom = strtolower(str_replace('/' . basename(ROOT_PATH), '', str_replace($config->BOT_NAME, '', explode(' ', $update['message']['text'])[0])));
    } else {
        info_log('BOT_NAME is missing! Please define it!', '!');
        $com = 'start';
        $altcom = 'start';
    }

    // Set command paths.
    $command = ROOT_PATH . '/commands/' . basename($com) . '.php';
    $altcommand = ROOT_PATH . '/commands/' . basename($altcom) . '.php';
    $core_command = CORE_COMMANDS_PATH . '/' . basename($com) . '.php';
    $core_altcommand = CORE_COMMANDS_PATH . '/' . basename($altcom) . '.php';
    $startcommand = ROOT_PATH . '/commands/start.php';

    // Write to log.
    debug_log(CORE_PATH,'Core path');
    debug_log('Command-File: ' . $command);
    debug_log('Alternative Command-File: ' . $altcommand);
    debug_log('Core Command-File: ' . $core_command);
    debug_log('Core Alternative Command-File: ' . $core_altcommand);
    debug_log('Start Command-File: ' . $startcommand);

    // Check if command file exits.
    if (is_file($command)) {
        // Dynamically include command file and exit.
        include_once($command);
    } else if (is_file($altcommand)) {
        // Dynamically include command file and exit.
        include_once($altcommand);
    } else if (is_file($core_command)) {
        // Dynamically include command file and exit.
        include_once($core_command);
    } else if (is_file($core_altcommand)) {
        // Dynamically include command file and exit.
        include_once($core_altcommand);
    } else if ($com == basename(ROOT_PATH)) {
        // Include start file and exit.
        include_once($startcommand);
    } else {
        sendMessage($update['message']['chat']['id'], '<b>' . getTranslation('not_supported') . '</b>');
    }
}
// IF a Message type is private and there is no leading '/'
else if($update['message']['chat']['type'] == 'private'){  
    // Get Message from User sent to Bot and check what to do
    evaluate_priv_message($update);
}