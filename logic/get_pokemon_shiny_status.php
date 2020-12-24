<?php
/**
 * Get pokemon shiny status.
 * @param $pokemon_id
 * @param $pokemon_form_id
 * @return string
 */
function get_pokemon_shiny_status($pokemon_id, $pokemon_form_id)
{
    if($pokemon_id !== "NULL" && $pokemon_id != 0) {
        // Get pokemon shiny status from database
        $rs = my_query(
                "
                SELECT    shiny
                FROM      pokemon
                WHERE     pokedex_id = {$pokemon_id}
                AND       pokemon_form_id = '{$pokemon_form_id}'
                "
            );

        // Fetch the row.
        $shiny = $rs->fetch();
        debug_log($shiny, 'Per db, shiny status is:');

        return $shiny['shiny'];
    } else {
        return 0;
   }
}

?>
