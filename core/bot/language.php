<?php
/**
 * Call the translation function with override parameters.
 * @param $text
 * @return translation
 */
function getPublicTranslation($text)
{
    global $config;
    $translation = getTranslation($text, true, $config->LANGUAGE_PUBLIC);

    return $translation;
}

/**
 * Gets a table translation out of the json file.
 * @param $text
 * @param $override
 * @param $override_language
 * @return translation
 */
function getTranslation($text, $override = false, $override_language = '')
{
    global $config;
    debug_log($text,'T:');
    $translation = '';
    $text = trim($text);

    $language = '';
    // Override language?
    if($override == true && $override_language != '') {
        $language = $override_language;
    } else {
        require_once('userlanguage.php');
        $language = USERLANGUAGE;
    }

    // Pokemon name?
    if(strpos($text, 'pokemon_id_') === 0) {
        // Translation filename
        $tfile = CORE_LANG_PATH . '/pokemon.json';

        // Get ID from string - e.g. 150 from pokemon_id_150
        $pokemon_id = substr($text, strrpos($text, '_') + 1);

        // Make sure we have a valid id.
        if(is_numeric($pokemon_id) && $pokemon_id > 0) {
            $str = file_get_contents($tfile);

            $json = json_decode($str, true);
            if(!isset($json[$text][$language])) {
                // If translation wasn't found, try to use english as fallback
                $language = DEFAULT_LANGUAGE;
            }
            if(!isset($json[$text][$language])) {
                $translation = false;
            }else {
                $translation = $json[$text][$language];
            }

        // Return false
        } else {
            debug_log($pokemon_id,'T: Received invalid pokemon id for translation:');
            $translation = false;
        }

    // Pokemon form?
    } else if(strpos($text, 'pokemon_form_') === 0) {
        // Translation filename
        $tfile = CORE_LANG_PATH . '/pokemon_forms.json';

        $str = file_get_contents($tfile);
        $json = json_decode($str, true);

    // Pokemon type?
    } else if(strpos($text, 'pokemon_type_') === 0) {
        // Translation filename
        $tfile = CORE_LANG_PATH . '/pokemon_types.json';

        $str = file_get_contents($tfile);
        $json = json_decode($str, true);
        
    // Pokemon moves?
    } else if(strpos($text, 'pokemon_move_') === 0) {
        // Translation filename
        $tfile = CORE_LANG_PATH . '/pokemon_moves.json';

        $str = file_get_contents($tfile);
        $json = json_decode($str, true);

    // Custom language file.
    } else if(is_file(CUSTOM_PATH . '/language.json')) {
        $tfile = CUSTOM_PATH . '/language.json';
            
        $str = file_get_contents($tfile);
        $json = json_decode($str, true);
    }

    // Other translation
    if(!(isset($json[$text]))){
        // Specific translation file?
        // E.g. Translation = hello_world_123, then check if hello_world.json exists.
        if(is_file(BOT_LANG_PATH . '/' . substr($text, 0, strrpos($text, '_')) . '.json')) {
            // Translation filename
            $tfile = BOT_LANG_PATH . '/' . substr($text, 0, strrpos($text, '_')) . '.json';

            $str = file_get_contents($tfile);
            $json = json_decode($str, true);

            // Core language file.
            if(!(isset($json[$text]))){
                // Translation filename
                $tfile = CORE_LANG_PATH . '/language.json';

                // Make sure file exists.
                if(is_file($tfile)) {
                    $str = file_get_contents($tfile);
                    $json = json_decode($str, true);
                }
            }
        }

        // Bot language file. 
        if(!(isset($json[$text]))){
            // Translation filename
            $tfile = BOT_LANG_PATH . '/language.json';

            // Make sure file exists.
            if(is_file($tfile)) {
                $str = file_get_contents($tfile);
                $json = json_decode($str, true);
            }
        }

        // Translation not in core or bot language file? - Try other core files.
        if(!(isset($json[$text]))){
            // Get all bot specific language files
            $langfiles = glob(CORE_LANG_PATH . '/*.json');

            // Find translation in the right file
            foreach($langfiles as $file) {
                $tfile = $file;
                $str = file_get_contents($file);
                $json = json_decode($str, true);
                // Exit foreach once found
                if(isset($json[$text])) {
                    break;
                }
            }
        }
 
        // Translation not in core or bot language file? - Try other bot files.
        if(!(isset($json[$text]))){
            // Get all bot specific language files
            $langfiles = glob(BOT_LANG_PATH . '/*.json');

            // Find translation in the right file
            foreach($langfiles as $file) {
                $tfile = $file;
                $str = file_get_contents($file);
                $json = json_decode($str, true);
                // Exit foreach once found
                if(isset($json[$text])) {
                    break;
                }
            }
        }
    }

    // Debug log translation file
    debug_log($tfile,'T:');

    // Return pokemon name or translation
    if(strpos($text, 'pokemon_id_') === 0) {
        return $translation;
    } else {
        // Fallback to English when there is no language key or translation is not yet done.
        if(isset($json[$text][$language]) && $json[$text][$language] != 'TRANSLATE'){
            $translation = $json[$text][$language];
        } else {
            $language = DEFAULT_LANGUAGE;
            if(isset($json[$text][$language])){
                $translation = $json[$text][$language];
            }else {
                $translation = '';
            }
        }
        //debug_log($translation,'T:');
        return $translation;
    }
}
