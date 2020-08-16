<?php
/**
 * Get pokemon weather.
 * @param $pokemon_id
 * @param $pokemon_form_id
 * @return string
 */
function get_pokemon_weather($pokemon_id, $pokemon_form_id)
{
    if($pokemon_id !== "NULL" && $pokemon_id != 0) {
        // Get pokemon weather from database
        $rs = my_query(
                "
                SELECT    weather
                FROM      pokemon
                WHERE     pokedex_id = {$pokemon_id}
                AND       pokemon_form = '{$pokemon_form_id}'
                "
            );

        // Fetch the row.
        $ww = $rs->fetch();

        return $ww['weather'];
    } else {
        return 0;
   }
}

?>
