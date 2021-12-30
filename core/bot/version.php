<?php
// Check if version is defined in config.
!empty($config->VERSION) or $config->VERSION = '1';
$current = $config->VERSION;

// Get version from VERSION file.
$lfile = ROOT_PATH . '/VERSION';
if(is_file($lfile) && filesize($lfile)) {
    $latest = file($lfile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $latest = $latest[0];
} else {
  $error = 'VERSION file missing, cannot continue without it since we would not know the required DB schema version.';
  info_log($error);
  throw new Exception($error);
}

// Compare versions.
if($current == $latest) {
    debug_log($current, 'Your bot schema version:');
} else {
    // Current version not defined in config!
    if($current == '1') {
        info_log('Failed to determine your bot version! Have you removed it from config.json?', '!');

        // Tell user bot maintainance is required!
        if(!empty($config->MAINTAINER_ID)) {
            $msg = 'ERROR! BOT MAINTAINANCE REQUIRED!' . CR . 'FAILED TO GET YOUR BOT SCHEMA VERSION!' . CR;
            $msg .= 'Server: ' . $_SERVER['SERVER_ADDR'] . CR;
            $msg .= 'User: ' . $_SERVER['REMOTE_ADDR'] . ' ' . isset($_SERVER['HTTP_X_FORWARDED_FOR']) . CR;
            $msg .= 'Your version: ' . $current . CR . 'Latest version: ' . $latest;
            sendMessageEcho($config->MAINTAINER_ID, $msg);
        } else {
            // Write to standard error log.
            error_log('ERROR! The config item MAINTAINER_ID is not defined!');
            error_log('ERROR! BOT MAINTAINANCE REQUIRED! FAILED TO GET YOUR BOT SCHEMA VERSION! --- Your version: ' . $current . ' --- Latest version: ' . $latest);
        }
        // Exit script.
        exit();
    } else {
        // Check for upgrade files.
        debug_log('Bot version: ' . $current . ', Latest: ' . $latest);
        $upgrade = bot_upgrade_check($current, $latest);

        // Manual upgrade needed?
        if($upgrade) {
            // Tell user an upgrade is required!
            if(!empty($config->MAINTAINER_ID)) {
                // Echo data.
                sendMessageEcho($config->MAINTAINER_ID, 'BOT SCHEMA NEEDS AN UPGRADE, and and admin to take a manual look at it.' . CR . 'Server: ' . $_SERVER['SERVER_ADDR'] . CR . 'User: ' . $_SERVER['REMOTE_ADDR'] . ' ' . isset($_SERVER['HTTP_X_FORWARDED_FOR']) . CR . 'Your version: ' . $current . CR . 'Latest version: ' . $latest);
            } else {
                // Write to standard error log.
                error_log('ERROR! The config item MAINTAINER_ID is not defined!');
                error_log('ERROR! BOT UPGRADE REQUIRED! --- Your version: ' . $current . ' --- Latest version: ' . $latest);
            }
            // Exit script.
            exit();
        }
    }
}
