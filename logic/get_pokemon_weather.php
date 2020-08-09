<?php
/**
 * Get pokemon weather.
 * @param $pokemon_id_form
 * @return string
 */
function get_pokemon_weather($pokemon_id_form)
{
    // Split pokedex_id and form
    $dex_id_form = explode('-',$pokemon_id_form);
    $pokedex_id = $dex_id_form[0];
    $pokemon_form = $dex_id_form[1];

    if($pokedex_id !== "NULL" && $pokedex_id != 0) {
        // Get pokemon weather from database
        $rs = my_query(
                "
                SELECT    weather
                FROM      pokemon
                WHERE     pokedex_id = {$pokedex_id}
                AND       pokemon_form = '{$pokemon_form}'
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
