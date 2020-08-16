<?php
/**
 * Get pokemon form name.
 * @param $pokedex_id
 * @param $pokemon_form_id
 * @return string
 */
function get_pokemon_form_name($pokedex_id, $pokemon_form_id)
{
    debug_log($pokedex_id.'-'.$pokemon_form_id, 'Finding Pokemon form name for:');

    // Make sure $dex_id is numeric
    if(is_numeric($pokedex_id) && is_numeric($pokemon_form_id)) {
        // Get raid level from database
        $rs = my_query(
                "
                SELECT    pokemon_form_name
                FROM      pokemon
                WHERE     pokedex_id = {$pokedex_id}
                AND       pokemon_form_id = '{$pokemon_form_id}'
                "
            );

        while ($level = $rs->fetch_assoc()) {
            $pokemon_form_name = $level['pokemon_form_name'];
        }
        debug_log($pokemon_form_name, 'Per db, level is:');
    } else {
        debug_log('Faulty dex_id or form_id, defaulting to normal.');
        $pokemon_form_name = 'normal';
    }

    return $pokemon_form_name;
}
?>