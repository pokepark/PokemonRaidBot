<?php
// Core symlinked?
if(is_link($parent . '/core')) {
    $corepath = readlink($parent . '/core');
    $corepath = rtrim($corepath, '/');
    define('ROOT_PATH', $parent);
    define('CORE_PATH', $corepath);

// Core inside bot dir
} else {
    define('ROOT_PATH', dirname(__DIR__,2));
    define('CORE_PATH', ROOT_PATH . '/core');
}

// Core Paths
define('CORE_TG_PATH', CORE_PATH . '/telegram');
define('CORE_BOT_PATH', CORE_PATH . '/bot');
define('CORE_LANG_PATH', CORE_PATH . '/lang');
define('CORE_COMMANDS_PATH', CORE_PATH . '/commands');
define('CORE_CLASS_PATH', CORE_PATH . '/class');

// Bot Paths
define('CONFIG_PATH', ROOT_PATH . '/config');
define('LOGIC_PATH', ROOT_PATH . '/logic');
define('BOT_LANG_PATH', ROOT_PATH . '/lang');
define('FONTS_PATH', ROOT_PATH . '/fonts');
define('IMAGES_PATH', ROOT_PATH . '/images');
define('ACCESS_PATH', ROOT_PATH . '/access');
define('DDOS_PATH', ROOT_PATH . '/ddos');
define('CUSTOM_PATH', ROOT_PATH . '/custom');
define('UPGRADE_PATH', ROOT_PATH . '/sql/upgrade');
