<?php
/**
 * Get local name of pokemon.
 * @param $pokemon_id
 * @param $pokemon_form_id
 * @param $override_language
 * @return string
 */
function get_local_pokemon_name($pokemon_id, $pokemon_form_id, $override_language = false)
{
    $q = my_query("SELECT pokemon_name, pokemon_form_name FROM pokemon WHERE pokedex_id = '{$pokemon_id}' AND pokemon_form_id = '{$pokemon_form_id}'");
    $res = $q->fetch();
    $pokemon_form_name = $res['pokemon_form_name'];

    debug_log('Pokemon_form: ' . $pokemon_form_name);
    
    // Get translation type
    if($override_language == true) {
        $getTypeTranslation = 'getPublicTranslation';
    } else {
        $getTypeTranslation = 'getTranslation';
    }
    // Init pokemon name and define fake pokedex ids used for raid eggs
    $pokemon_name = '';
    $eggs = $GLOBALS['eggs'];

    // Get eggs from normal translation.
    if(in_array($pokemon_id, $eggs)) {
        $pokemon_name = $getTypeTranslation('egg_' . substr($pokemon_id, -1));
    } else {
        $pokemon_name = $getTypeTranslation('pokemon_id_' . $pokemon_id);
    }
    if ($pokemon_form_name != 'normal') {
        $pokemon_form_name = $getTypeTranslation('pokemon_form_' . $pokemon_form_name);
    } 
    // Fallback 1: Valid pokedex id or just a raid egg?
    if($pokemon_id === "NULL" || $pokemon_id == 0) {
        $pokemon_name = $getTypeTranslation('egg_0');

    // Fallback 2: Get original pokemon name and/or form name from database
    } else if(empty($pokemon_name) or empty($pokemon_form_name)) {
        // Pokemon name
        $pokemon_name = (empty($pokemon_name)?$res['pokemon_name']:$pokemon_name);
        // Pokemon form
        if(empty($pokemon_form_name) && $res['pokemon_form_name'] != 'normal') {
            $pokemon_form_name = ucfirst(str_replace("_"," ",$res['pokemon_form_name']));
        }
    }

    return $pokemon_name . ($pokemon_form_name != "normal" ? " " . $pokemon_form_name : "");
}

?>
