<?php

function resolve_boss_name_to_ids($pokemon_name) {
    // Pokemon name ending with "_FORM" ?
    if(substr_compare($pokemon_name, '_FORM', -strlen('_FORM')) === 0) {
        debug_log('Pokemon with a special form received: ' . $pokemon_name);
        // Remove "_FORM"
        $pokemon = str_replace('_FORM', '', $pokemon_name);

        // Get pokemon name and form.
        $name = explode("_", $pokemon, 2)[0];
        $form = explode("_", $pokemon, 2)[1];

        // Fix for MEWTWO_A_FORM
        if($name == 'MEWTWO' && $form == 'A') {
            $form = 'ARMORED';
        }

    // Pokemon name ending with "_MALE" ?
    } else if(substr_compare($pokemon_name, '_MALE', -strlen('_MALE')) === 0) {
        debug_log('Pokemon with gender MALE received: ' . $pokemon_name);
        // Remove "_MALE"
        $pokemon = str_replace('_MALE', '', $pokemon_name);

        // Get pokemon name and form.
        $name = explode("_", $pokemon, 2)[0] . '♂';
        $form = 'normal';

    // Pokemon name ending with "_FEMALE" ?
    } else if(substr_compare($pokemon_name, '_FEMALE', -strlen('_FEMALE')) === 0) {
        debug_log('Pokemon with gender FEMALE received: ' . $pokemon_name);
        // Remove "_FEMALE"
        $pokemon = str_replace('_FEMALE', '', $pokemon_name);

        // Get pokemon name and form.
        $name = explode("_", $pokemon, 2)[0] . '♀';
        $form = 'normal';

    // Mega pokemon ?
    }else if(substr_compare($pokemon_name, '_MEGA', -strlen('_MEGA')) === 0 or substr_compare($pokemon_name, '_MEGA_X', -strlen('_MEGA_X')) === 0 or substr_compare($pokemon_name, '_MEGA_Y', -strlen('_MEGA_Y')) === 0) {
        debug_log('Mega Pokemon received: ' . $pokemon_name);

        // Get pokemon name and form.
        $name_form = explode("_", $pokemon_name, 2);
        $name = $name_form[0];
        $form = $name_form[1];

    // Normal pokemon without form or gender.
    } else {
        // Fix pokemon like "HO_OH"...
        if(substr_count($pokemon_name, '_') >= 1) {
            $pokemon = str_replace('_', '-', $pokemon_name);
        } else {
            $pokemon = $pokemon_name;
        }
        // Name and form.
        $name = $pokemon;
        $form = 'normal';

        // Fix for GIRATINA as the actual GIRATINA_ALTERED_FORM is just GIRATINA
        if($name == 'GIRATINA' && $form == 'normal') {
            $form = 'ALTERED';
        }
    }
    // Get ID and form name used internally.
    debug_log('Getting dex id and form for pokemon ' . $name . ' with form ' . $form);
    return get_pokemon_id_by_name($name . '-' . $form, true);
}

?>