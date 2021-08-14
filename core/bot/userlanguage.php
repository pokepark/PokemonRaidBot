<?php
// Write to log.
debug_log('Language Check');

// Get language from user - otherwise use language from config.
if ($config->LANGUAGE_PRIVATE == '') {
    // Message or callback?
    if(isset($update['message']['from'])) {
        $from = $update['message']['from'];
    } else if(isset($update['callback_query']['from'])) {
        $from = $update['callback_query']['from'];
    } else if(isset($update['inline_query']['from'])) {
        $from = $update['inline_query']['from'];
    }
    $q = my_query("SELECT lang FROM users WHERE user_id='".$from['id']."' LIMIT 1");
    $res = $q->fetch();
    $language_code = $res['lang'];

    // Get and define userlanguage.
    $languages = $GLOBALS['languages'];

    // Get languages from normal translation.
    if(array_key_exists($language_code, $languages)) {
        $userlanguage = $languages[$language_code];
    } else {
        $userlanguage = DEFAULT_LANGUAGE;
    }

    debug_log('User language: ' . $userlanguage);
    define('USERLANGUAGE', $userlanguage);
} else {
    // Set user language to language from config.
    define('USERLANGUAGE', $config->LANGUAGE_PRIVATE);
}
