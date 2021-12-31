<?php

-require_once(CORE_BOT_PATH . '/logic/bot_upgrade_check.php');

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
  throw new Exception($error);
}

// Current version not defined in config!
if($current == '1') {
  $error = "Failed to determine your bot version! Have you removed it from config.json? or not defined POKEMONRAIDBOT_VERSION ? If this is a new installation, use the value {$latest}";
  throw new Exception($error);
}

// Check if we have the latest version and perform upgrade if needed & possible.
bot_upgrade_check($current, $latest);
