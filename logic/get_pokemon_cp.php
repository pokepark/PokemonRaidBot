<?php
/**
 * Get pokemon cp values.
 * @param $pokemon_id
 * @param $pokemon_form_id
 * @return array
 */
function get_pokemon_cp($pokemon_id, $pokemon_form_id)
{
    // Get gyms from database
    $rs = my_query(
            "
            SELECT    min_cp, max_cp, min_weather_cp, max_weather_cp
            FROM      pokemon
            WHERE     pokedex_id = {$pokemon_id}
            AND       pokemon_form = '{$pokemon_form_id}'
            "
        );

    $cp = $rs->fetch();

    return $cp;
}

?>
