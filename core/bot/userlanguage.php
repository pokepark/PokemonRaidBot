<?php
// Write to log.
debug_log('Language Check');

// Get language from user - otherwise use language from config.
if ($config->LANGUAGE_PRIVATE == '') {
    // Message or callback?
    if(isset($update['message']['from']['language_code'])) {
        $language_code = $update['message']['from']['language_code'];
    } else if(isset($update['callback_query']['from']['language_code'])) {
        $language_code = $update['callback_query']['from']['language_code'];
    } else {
        $language_code = $config->LANGUAGE_PUBLIC;
    }

    // Get and define userlanguage.
    $languages = $GLOBALS['languages'];

    // Get languages from normal translation.
    if(array_key_exists($language_code, $languages)) {
        $userlanguage = $languages[$language_code];
    } else {
        $userlanguage = $config->DEFAULT_LANGUAGE;
    }

    debug_log('User language: ' . $userlanguage);
    define('USERLANGUAGE', $userlanguage);
} else {
    // Set user language to language from config.
    define('USERLANGUAGE', $config->LANGUAGE_PRIVATE);
}
