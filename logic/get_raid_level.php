<?php
/**
 * Get raid level of a pokemon.
 * @param $pokedex_id
 * @param $pokemon_form_id
 * @return string
 */
function get_raid_level($pokedex_id, $pokemon_form_id)
{
    // Make sure $dex_id is numeric
    if(is_numeric($pokedex_id)) {
        // Get raid level from database
        $rs = my_query(
                "
                SELECT    raid_level
                FROM      pokemon
                WHERE     pokedex_id = {$pokedex_id}
                AND       pokemon_form_id = '{$pokemon_form_id}'
                "
            );

        $raid_level = '0';
        while ($level = $rs->fetch()) {
            $raid_level = $level['raid_level'];
        }
        debug_log("Resolved level of {$pokedex_id}({$pokemon_form_id}) to {$raid_level}");
    } else {
        info_log("Could not resolve level of {$pokedex_id}({$pokemon_form_id}), defaulting to 0!");
        $raid_level = '0';
    }

    return $raid_level;
}

?>
