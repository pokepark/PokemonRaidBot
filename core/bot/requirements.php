<?php
/*
 * Require all function files once
 */

// Core Paths Constants
require_once(__DIR__ . '/paths.php');

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

// Debug
require_once(CORE_BOT_PATH . '/debug.php');

// Telegram Core
require_once(CORE_TG_PATH . '/functions.php');

// Language
require_once(CORE_BOT_PATH . '/language.php');

// Timezone
require_once(CORE_BOT_PATH . '/timezone.php');

// Core Logic
require_once(CORE_BOT_PATH . '/logic.php');

// Bot Logic
if(is_file(ROOT_PATH . '/logic.php')) {
    require_once(ROOT_PATH . '/logic.php');
}

// Bot version
require_once(CORE_BOT_PATH . '/version.php');

// Geo API
require_once(CORE_BOT_PATH . '/geo_api.php');
