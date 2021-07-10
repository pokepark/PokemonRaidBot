<?php
// Check if version is defined in config.
!empty($config->VERSION) or $config->VERSION = '1.0.0,0';
$current = $config->VERSION;
$nodot_current = str_replace('.', '', $current);

// Get version from VERSION file.
$lfile = ROOT_PATH . '/VERSION';
if(is_file($lfile) && filesize($lfile)) {
    $latest = file($lfile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $latest = $latest[0];
} else {
    $latest = '1.0.0.0';
}
$nodot_latest = str_replace('.', '', $latest);

// Compare versions.
if($nodot_current == $nodot_latest) {
    debug_log($current, 'Your bot version:');
} else {
    // Current version not defined in config!
    if($nodot_current == '1000') { 
        info_log('Failed to determine your bot version!', '!');

        // Tell user bot maintainance is required!
        if(!empty($config->MAINTAINER_ID)) {
            // Echo data.
            $msg = 'ERROR! BOT MAINTAINANCE REQUIRED!' . CR . 'FAILED TO GET YOUR BOT VERSION!' . CR;
            $msg .= 'Server: ' . $_SERVER['SERVER_ADDR'] . CR;
            $msg .= 'User: ' . $_SERVER['REMOTE_ADDR'] . ' ' . isset($_SERVER['HTTP_X_FORWARDED_FOR']) . CR;
            $msg .= 'Your version: ' . $current . CR . 'Latest version: ' . $latest;
            sendMessageEcho($config->MAINTAINER_ID, $msg);
        } else {
            // Write to standard error log.
            error_log('ERROR! The config item MAINTAINER_ID is not defined!');
            error_log('ERROR! BOT MAINTAINANCE REQUIRED! FAILED TO GET YOUR BOT VERSION! --- Your version: ' . $current . ' --- Latest version: ' . $latest);
        }
        // Exit script.
        exit();

    // Latest version unavailable!
    } else if($nodot_latest == '1000') {
        info_log('Failed to determine the latest bot version!', '!');

        // Tell user bot maintainance is required!
        if(!empty($config->MAINTAINER_ID)) {
            // Echo data.
            $msg = 'ERROR! BOT MAINTAINANCE REQUIRED!' . CR . 'FAILED TO GET THE LATEST BOT VERSION!' . CR;
            $msg .= 'Server: ' . $_SERVER['SERVER_ADDR'] . CR;
            $msg .= 'User: ' . $_SERVER['REMOTE_ADDR'] . ' ' . isset($_SERVER['HTTP_X_FORWARDED_FOR']) . CR;
            $msg .= 'Your version: ' . $current . CR . 'Latest version: ' . $latest;
            sendMessageEcho($config->MAINTAINER_ID, $msg);
        } else {
            // Write to standard error log.
            error_log('ERROR! The config item MAINTAINER_ID is not defined!');
            error_log('ERROR! BOT MAINTAINANCE REQUIRED! FAILED TO GET THE LATEST BOT VERSION! --- Your version: ' . $current . ' --- Latest version: ' . $latest);
        } 
        // Exit script.
        exit();

    // Check for upgrade files.
    } else {
        debug_log('Bot version: ' . $current . ', Latest: ' . $latest);
        require_once('db.php');
        $upgrade = bot_upgrade_check($current, $latest, $dbh);

        // Upgrade needed?
        if($upgrade) {
            // Tell user an upgrade is required!
            if(!empty($config->MAINTAINER_ID)) {
                // Echo data.
                sendMessageEcho($config->MAINTAINER_ID, 'ERROR! BOT UPGRADE REQUIRED!' . CR . 'Server: ' . $_SERVER['SERVER_ADDR'] . CR . 'User: ' . $_SERVER['REMOTE_ADDR'] . ' ' . isset($_SERVER['HTTP_X_FORWARDED_FOR']) . CR . 'Your version: ' . $current . CR . 'Latest version: ' . $latest);
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
