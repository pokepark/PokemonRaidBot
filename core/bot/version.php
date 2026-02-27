<?php

require_once(ROOT_PATH . '/logic/bot_upgrade_check.php');

// Get version from VERSION file.
$lfile = ROOT_PATH . '/VERSION';
if(!is_file($lfile) || !filesize($lfile)) {
  $error = 'VERSION file missing, cannot continue without it since we would not know the required DB schema version.';
  throw new Exception($error);
}
$latest = file($lfile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$latest = $latest[0];

// Check if version is defined in config.
if(!isset($config->VERSION) or empty($config->VERSION) or $config->VERSION == '1') {
  $error = 'Failed to determine your bot version! Have you removed it from config.json? or not defined POKEMONRAIDBOT_VERSION ? If this is a new installation, use the value ' . $latest;
  throw new Exception($error);
}
$current = $config->VERSION;

// Check if we have the latest version and perform upgrade if needed & possible.
bot_upgrade_check($current, $latest);
