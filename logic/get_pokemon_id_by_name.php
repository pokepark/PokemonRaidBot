<?php
/**
 * Get pokedex id by name of pokemon.
 * @param $pokemon_name
 * @param $get_from_db bool
 * @return string
 */
function get_pokemon_id_by_name($pokemon_name, $get_from_db = false)
{
    global $dbh;
    debug_log($pokemon_name,'P:');

    // Explode pokemon name in case we have a form too.
    $delimiter = '';
    if(strpos($pokemon_name, ' ') !== false) {
        $delimiter = ' ';
    } else if (strpos($pokemon_name, '-') !== false) {
        $delimiter = '-';
    } else if (strpos($pokemon_name, ',') !== false) {
        $delimiter = ',';
    }

    // Explode if delimiter was found.
    $poke_name = $pokemon_name;
    $poke_form = "";
    if($delimiter != '') {
        $pokemon_name_form = explode($delimiter,$pokemon_name,2);
        $poke_name = trim($pokemon_name_form[0]);
        $poke_name = strtolower($poke_name);
        $poke_form = trim($pokemon_name_form[1]);
        $poke_form = strtolower($poke_form);
        debug_log($poke_name,'P NAME:');
        debug_log($poke_form,'P FORM:');
    }
    
    // Init id and write name to search to log.
    $pokemon_id = 0;
    $pokemon_form = ($poke_form!="")?$poke_form:"normal";

    // Set language
    $language = USERLANGUAGE;
    if($get_from_db) {
        // Fetch Pokemon form ID from database
        $stmt = $dbh->prepare("
                SELECT  pokedex_id, pokemon_form_id 
                FROM    pokemon 
                WHERE   pokemon_name = :poke_name
                AND     pokemon_form_name = :form_name
                LIMIT   1
                ");
        $stmt->execute(['poke_name' => $poke_name, 'form_name' => $pokemon_form]);
        $res = $stmt->fetch();
        $pokemon_form_id = $res['pokemon_form_id'];
        $pokemon_id = $res['pokedex_id'];
    }else {
        // Get translation file
        $str = file_get_contents(CORE_LANG_PATH . '/pokemon.json');
        $json = json_decode($str, true);
        $search_result = "";
        foreach($json as $title => $translations) {
            // Try to find translation for userlanguage
            if(ucfirst($poke_name) == $translations[$language]) {
                $search_result = $title;
                debug_log('Translation found for lang: '.$language, 'P:');
                debug_log('Translation result: '.$title, 'P:');
                break;
            // Also look for fallback in default language
            }elseif(ucfirst($poke_name) == $translations[DEFAULT_LANGUAGE]) {
                $search_result = $title;
            }
        }
        if($search_result != "") {
            $pokemon_id = str_replace('pokemon_id_','', $search_result);
        }else {
            // Debug log.
            debug_log('Error! Pokedex ID could not be found for pokemon with name: ' . $poke_name);
        }

        // Get form.
        // Works like this: Search form in language file via language, e.g. 'DE' and local form translation, e.g. 'Alola' for 'DE'.
        // In additon we are searching the DEFAULT_LANGUAGE and the key name for the form name.
        // Once we found the key name, e.g. 'pokemon_form_attack', get the form name 'attack' from it via str_replace'ing the prefix 'pokemon_form'.
        if($pokemon_id != 0 && isset($poke_form) && !empty($poke_form) && $poke_form != 'normal') {
            debug_log('Searching for pokemon form: ' . $poke_form);

            // Get forms translation file
            $str_form = file_get_contents(CORE_LANG_PATH . '/pokemon_forms.json');
            $json_form = json_decode($str_form, true);

            // Search pokemon form in json
            foreach($json_form as $key_form => $jform) {
                // Stop search if we found it.
                if ($jform[$language] === ucfirst($poke_form)) {
                    $pokemon_form = str_replace('pokemon_form_','',$key_form);
                    debug_log('Found pokemon form by user language: ' . $language);
                    break;

                // Try DEFAULT_LANGUAGE too.
                } else if ($jform[DEFAULT_LANGUAGE] === ucfirst($poke_form)) {
                    $pokemon_form = str_replace('pokemon_form_','',$key_form);
                    debug_log('Found pokemon form by default language: ' . DEFAULT_LANGUAGE);
                    break;

                // Try key name.
                } else if ($key_form === ('pokemon_form_' . $poke_form)) {
                    $pokemon_form = str_replace('pokemon_form_','',$key_form);
                    debug_log('Found pokemon form by json key name: pokemon_form_' . $key_form);
                    break;
                }
            }
        }
        // Fetch Pokemon form ID from database
        $stmt = $dbh->prepare("
                SELECT  pokemon_form_id
                FROM    pokemon
                WHERE   pokedex_id = :pokedex_id
                AND     pokemon_form_name = :form_name
                LIMIT   1
                ");
        $stmt->execute(['pokedex_id' => $pokemon_id, 'form_name' => $pokemon_form]);
        $res = $stmt->fetch();
        $pokemon_form_id = $res['pokemon_form_id'];
    }

    // Write to log.
    debug_log($pokemon_id,'P:');
    debug_log($pokemon_form.' (ID: '.$pokemon_form_id.')','P:');

    // Set pokemon form.
    $pokemon_id = $pokemon_id . '-' . $pokemon_form_id;

    // Return pokemon_id
    return $pokemon_id;
}

?>
