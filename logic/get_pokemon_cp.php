<?php
/**
 * Get pokemon cp values.
 * @param $pokemon_id_form
 * @return array
 */
function get_pokemon_cp($pokemon_id_form)
{
    // Split pokedex_id and form
    $dex_id_form = explode('-',$pokemon_id_form);
    $pokedex_id = $dex_id_form[0];
    $pokemon_form = $dex_id_form[1];

    // Get gyms from database
    $rs = my_query(
            "
            SELECT    min_cp, max_cp, min_weather_cp, max_weather_cp
            FROM      pokemon
            WHERE     pokedex_id = {$pokedex_id}
            AND       pokemon_form = '{$pokemon_form}'
            "
        );

    $cp = $rs->fetch();

    return $cp;
}

?>
