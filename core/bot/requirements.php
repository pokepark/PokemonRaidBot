<?php
/*
 * Require all function files once
 */

// Core Paths Constants
require_once(__DIR__ . '/paths.php');

// Exception & error handling
require_once(CORE_BOT_PATH . '/error_handlers.php');

// Custom Bot Constants
if(is_file(CUSTOM_PATH . '/constants.php')) {
    require_once(CUSTOM_PATH . '/constants.php');
}

// Core Constants
require_once(CORE_BOT_PATH . '/constants.php');

// Bot Constants
if(is_file(ROOT_PATH . '/constants.php')) {
    require_once(ROOT_PATH . '/constants.php');
}

// Config
require_once(CORE_BOT_PATH . '/config.php');

// Logging functions
require_once(CORE_BOT_PATH . '/logic/debug.php');

// SQL Utils
require_once(CORE_BOT_PATH . '/logic/sql_utils.php');

// Database connection
require_once(CORE_BOT_PATH . '/db.php');

// Core Logic
require_once(CORE_BOT_PATH . '/logic.php');

// Telegram Core
require_once(CORE_TG_PATH . '/functions.php');

// Timezone
require_once(CORE_BOT_PATH . '/timezone.php');

// Bot Logic
if(is_file(ROOT_PATH . '/logic.php')) {
    require_once(ROOT_PATH . '/logic.php');
}

// Bot version
require_once(CORE_BOT_PATH . '/version.php');

// Optionally load Composer autoloads. It's not yet a strict requirement for the majority of the project
if (is_file(ROOT_PATH . '/vendor/autoload.php')) {
  require_once(ROOT_PATH . '/vendor/autoload.php');

  // Init features that require Composer loaded classes
  require_once(CORE_BOT_PATH . '/metrics.php');
} else {
  // Any feature object that can't be used due to missing autoloads should be declared here as a falsy value.
  $metrics = NULL;
}
