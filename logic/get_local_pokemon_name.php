<?php
/**
 * Get local name of pokemon.
 * @param $pokemon_id_form
 * @param $override_language
 * @return string
 */
function get_local_pokemon_name($pokemon_id_form, $override_language = false)
{
    // Split pokedex_id and form
    $dex_id_form = explode('-',$pokemon_id_form);
    $pokedex_id = $dex_id_form[0];
    $pokemon_form = $dex_id_form[1];

    debug_log('Pokemon_form: ' . $pokemon_form);

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
    if(in_array($pokedex_id, $eggs)) {
        $pokemon_name = $getTypeTranslation('egg_' . substr($pokedex_id, -1));
    } else if ($pokemon_form != 'normal') {
        $pokemon_name = $getTypeTranslation('pokemon_id_' . $pokedex_id);
        $pokemon_name = (!empty($pokemon_name)) ? ($pokemon_name . SP . $getTypeTranslation('pokemon_form_' . $pokemon_form)) : '';
    } else {
        $pokemon_name = $getTypeTranslation('pokemon_id_' . $pokedex_id);
    }

    // Fallback 1: Valid pokedex id or just a raid egg?
    if($pokedex_id === "NULL" || $pokedex_id == 0) {
        $pokemon_name = $getTypeTranslation('egg_0');

    // Fallback 2: Get original pokemon name from database
    } else if(empty($pokemon_name)) {
        $rs = my_query(
                "
                SELECT    pokemon_name, pokemon_form
                FROM      pokemon
                WHERE     pokedex_id = {$pokedex_id}
                AND       pokemon_form = '{$pokemon_form}'
                "
            );

        while ($pokemon = $rs->fetch()) {
            // Pokemon name
            $pokemon_name = $pokemon['pokemon_name'];
            // Pokemon form
            if(!empty($pokemon['pokemon_form']) && $pokemon['pokemon_form'] != 'normal') {
                $pokemon_form = $getTypeTranslation('pokemon_form_' . $pokemon['pokemon_form']);
                $pokemon_name = (!empty($pokemon_form)) ? ($pokemon_name . SP . $pokemon_form) : ($pokemon_name . SP . ucfirst($pokemon['pokemon_form']));
            }
        }
    }

    return $pokemon_name;
}

?>
