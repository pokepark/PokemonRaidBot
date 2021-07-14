<?php
/**
 * Get user language.
 * @param $language_code
 * @return string
 */
function get_user_language($language_code)
{
    global $config;
    $languages = $GLOBALS['languages'];

    // Get languages from normal translation.
    if(array_key_exists($language_code, $languages)) {
        $userlanguage = $languages[$language_code];
    } else {
        $userlanguage = $config->DEFAULT_LANGUAGE;
    }

    debug_log('User language: ' . $userlanguage);

    return $userlanguage;
}


?>
