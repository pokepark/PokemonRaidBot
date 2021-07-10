<?php
// Set error reporting in debug mode.
if ($config->DEBUG === true) {
    error_reporting(E_ALL ^ E_NOTICE);
}

// Tell telegram / incoming webhook 'OK'
http_response_code(200);

// Get current unix timestamp as float.
$start = microtime(true);

// Get api key from get parameters.
if(isset($_GET['apikey'])) {
    $apiKey = $_GET['apikey'];
// Get api key from argv.
} elseif(!empty($argv[1])) {
    $apiKey = $argv[1];
} else {
    $apiKey = 'MISSING!';
    debug_log('Called without apikey, nothing will likely work.');
}

// Check if hashed api key is matching config.
if (hash('sha512', $apiKey) == strtolower($config->APIKEY_HASH)) {
    // Split the api key.
    $splitKey = explode(':', $apiKey);

    // Set constants.
    define('API_KEY', $apiKey);

// Api key is wrong!
} else {
    if(!empty($config->MAINTAINER_ID)) {
        // Echo data.
        sendMessageEcho($config->MAINTAINER_ID, 'ERROR! WRONG APIKEY!' . CR . 'Server: ' . $_SERVER['SERVER_ADDR'] . CR . 'User: ' . $_SERVER['REMOTE_ADDR'] . ' ' . isset($_SERVER['HTTP_X_FORWARDED_FOR']) . CR . 'APIKEY: ' . $apiKey);
    } else {
        // Write to standard error log.
        error_log('ERROR! The config option MAINTAINER_ID is not defined!');
    }
    // And exit script.
    exit();
}

// Get content if not already
if (!(isset($update))) {
    // Get content from POST data.
    $content = file_get_contents('php://input');

    if($content) {
        // Decode the json string.
        $update = json_decode($content, true);
    } elseif(!empty($argv[2])) {
        $arg_content = addslashes($argv[2]);
        $update = json_decode($argv[2], true);
    }
} else {
    debug_log('Already got content from POST data', '!');
}

if ($update) {
  debug_log_incoming($update, '<');
} else {
  // Log non-updates with a ! prefix
  debug_log($update, '!');
}
