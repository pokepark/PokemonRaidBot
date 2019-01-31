<?php
// Include files.
require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/core/class/constants.php');
require_once(__DIR__ . '/core/class/debug.php');
require_once(__DIR__ . '/core/class/functions.php');
require_once(__DIR__ . '/core/class/geo_api.php');
require_once(__DIR__ . '/logic.php');

// Start logging.
debug_log("RAID-BOT '" . BOT_ID . "'");

// Set time zone as configured in the config file
$tz = TIMEZONE;
date_default_timezone_set($tz);

// Check API Key and get input from telegram
include_once(CORECLASS_PATH . '/apikey.php');

// DDOS protection
include_once(CORECLASS_PATH . '/ddos.php');

// Get language
include_once(CORECLASS_PATH . '/language.php');

// Update var is false.
$log_prefix = '<';
if (!$update) {
    $log_prefix = '!';
}

// Write to log.
debug_log($update, $log_prefix);

$dbh = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASSWORD, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
$dbh->setAttribute(PDO::ATTR_ORACLE_NULLS, PDO::NULL_EMPTY_STRING);

// Establish mysql connection.
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
$db->set_charset('utf8mb4');

// Error connecting to db.
if ($db->connect_errno) {
    // Write connection error to log.
    debug_log("Failed to connect to Database!" . $db->connect_error(), '!');
    // Echo data.
    sendMessage($update['message']['chat']['id'], "Failed to connect to Database!\nPlease contact " . MAINTAINER . " and forward this message...\n");
    // Exit.
    exit();
}

// Run cleanup if requested
include_once(CORECLASS_PATH . '/cleanup_run.php');

// Update the user
if ($ddos_count < 2) {
    // Update the user.
    $userUpdate = update_user($update);

    // Write to log.
    debug_log('Update user: ' . $userUpdate);
}

// Callback query received.
if (isset($update['callback_query'])) {
    // Init empty data array.
    $data = [];

    // Callback data found.
    if ($update['callback_query']['data']) {
        // Bridge mode?
        if(defined('BRIDGE_MODE') && BRIDGE_MODE == true) {
            // Split bot folder name away from actual data.
            $botnameData = explode(':', $update['callback_query']['data'], 2);
            $botname = $botnameData[0];
            $thedata = $botnameData[1];

            // Write to log
            debug_log('Bot Name: ' . $botname);
            debug_log('The Data: ' . $thedata);
        } else {
            // Data is just the data.
            $thedata = $update['callback_query']['data'];
        }

        // Split callback data and assign to data array.
        $splitData = explode(':', $thedata);
        $data['id']     = $splitData[0];
        $data['action'] = $splitData[1];
        $data['arg']    = $splitData[2];
    }

    // Write data to log.
    debug_log($data, '* DATA= ');

    // Set module path by sent action name.
    $module = ROOT_PATH . '/mods/' . basename($data['action']) . '.php';

    // Write module to log.
    debug_log($module);

    // Check if the module file exists.
    if (file_exists($module)) {
        // Dynamically include module file and exit.
        include_once($module);

    // Module file is missing.
    } else {
        // Write to log.
        debug_log('No action');
    }

// Inline query received.
} else if (isset($update['inline_query'])) {
    // Check access to the bot
    bot_access_check($update);
    // List polls and exit.
    raid_list($update);

// Location received.
} else if (isset($update['message']['location'])) {
    // Check access to the bot
    bot_access_check($update);

    // Create raid and exit.
    if(RAID_VIA_LOCATION == true) {
        include_once(ROOT_PATH . '/mods/raid_by_location.php');
    }

// Cleanup collection from channel/supergroup messages.
} else if ((isset($update['channel_post']) && $update['channel_post']['chat']['type'] == "channel") || (isset($update['message']) && $update['message']['chat']['type'] == "supergroup")) {
    // Write to log.
    debug_log('Collecting cleanup preparation information...');

    // Init ID.
    $id = 0;

    // Channel 
    if(isset($update['channel_post'])) {
        // Get chat_id and message_id
        $chat_id = $update['channel_post']['chat']['id'];
        $message_id = $update['channel_post']['message_id'];

	// Get id from text.
        $id = substr(strrchr($update['channel_post']['text'], substr(strtoupper(BOT_ID), 0, 1) . '-ID = '), 7);

    // Supergroup
    } else if ($update['message']['chat']['type'] == "supergroup") {
        // Get chat_id and message_id
        $chat_id = $update['message']['chat']['id'];
        $message_id = $update['message']['message_id'];

	// Get id from text.
        $id = substr(strrchr($update['message']['text'], substr(strtoupper(BOT_ID), 0, 1) . '-ID = '), 7);
    }

    // Write cleanup info to database.
    debug_log('Calling cleanup preparation now!');
    if($id != 0) {
        insert_cleanup($chat_id, $message_id, $id);
    }

// Message is required to check for commands.
} else if (isset($update['message']) && ($update['message']['chat']['type'] == 'private' || $update['message']['chat']['type'] == 'channel')) {
    // Check access to the bot
    bot_access_check($update);

    // Init command.
    $command = NULL;

    // Check message text for a leading slash.
    if (substr($update['message']['text'], 0, 1) == '/') {
        // Get command name.
        $com = strtolower(str_replace('/', '', str_replace(BOT_NAME, '', explode(' ', $update['message']['text'])[0])));
        $altcom = strtolower(str_replace('/' . basename(ROOT_PATH), '', str_replace(BOT_NAME, '', explode(' ', $update['message']['text'])[0])));

        // Set command paths.
        $command = ROOT_PATH . '/commands/' . basename($com) . '.php';
        $altcommand = ROOT_PATH . '/commands/' . basename($altcom) . '.php';
        $startcommand = ROOT_PATH . '/commands/start.php';

        // Write to log.
        debug_log('Command-File: ' . $command);
        debug_log('Alternative Command-File: ' . $altcommand);
        debug_log('Start Command-File: ' . $startcommand);

        // Check if command file exits.
        if (is_file($command)) {
            // Dynamically include command file and exit.
            include_once($command);
        } else if (is_file($altcommand)) {
            // Dynamically include command file and exit.
            include_once($altcommand);
        } else if ($com == basename(ROOT_PATH)) {
            // Include start file and exit.
            include_once($startcommand);
        } else {
            sendMessage($update['message']['chat']['id'], '<b>' . getTranslation('not_supported') . '</b>');
        }
    }
}

$dbh = null;
?>
