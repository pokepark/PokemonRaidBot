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
                AND       pokemon_form_id = '{$pokemon_form_id}'
                "
            );

        $ww = $rs->fetch();

        if($ww) {
          return $ww['weather'];
        } else {
          throw new Exception("Failed to find pokemon {$pokemon_id}_{$pokemon_form_id} weather.");
        }
    } else {
        return 0;
   }
}

?>
