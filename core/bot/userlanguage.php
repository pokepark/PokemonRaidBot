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
        $language_code = $config->LANGUAGE_PRIVATE;
    }

    // Get and define userlanguage.
    $userlanguage = get_user_language($language_code);
    define('USERLANGUAGE', $userlanguage);
} else {
    // Set user language to language from config.
    define('USERLANGUAGE', $config->LANGUAGE_PRIVATE);
}
