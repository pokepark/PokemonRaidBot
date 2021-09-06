<?php
/**
 * Get pokedex_id and pokemon_form_id with id from database.
 * @param $pokemon_table_id
 * @return array
 */
function get_pokemon_by_table_id($pokemon_table_id) {
    $q = my_query("
            SELECT  pokedex_id, 
                    pokemon_form_id
            FROM    pokemon
            WHERE   id = {$pokemon_table_id}
            LIMIT   1
            ");
    $return = $q->fetch();
    return $return;
}