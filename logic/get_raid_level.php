<?php
/**
 * Get raid level of a pokemon.
 * @param $pokedex_id
 * @return string
 */
function get_raid_level($pokedex_id)
{
    debug_log($pokedex_id, 'Finding level for:');
    // Split pokedex_id and form
    $dex_id_form = explode('-',$pokedex_id);
    $dex_id = $dex_id_form[0];
    $dex_form = $dex_id_form[1];

    // Make sure $dex_id is numeric
    if(is_numeric($dex_id)) {
        // Get raid level from database
        $rs = my_query(
                "
                SELECT    raid_level
                FROM      pokemon
                WHERE     pokedex_id = {$dex_id}
                AND       pokemon_form = '{$dex_form}'
                "
            );

        $raid_level = '0';
        while ($level = $rs->fetch_assoc()) {
            $raid_level = $level['raid_level'];
        }
        debug_log($raid_level, 'Per db, level is:');
    } else {
        debug_log('Faulty dex_id, defaulting to level 0.');
        $raid_level = '0';
    }

    return $raid_level;
}

?>
