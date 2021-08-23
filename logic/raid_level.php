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
                FROM      raid_bosses
                WHERE     pokedex_id = '{$pokedex_id}'
                AND       pokemon_form_id = '{$pokemon_form_id}'
                AND       date_start = '1970-01-01 00:00:01'
                AND       date_end = '2038-01-19 03:14:07'
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

/**
 * Get active raid bosses at a certain time.
 * @param $time - string, datetime, local time
 * @param $raid_level - ENUM('1', '2', '3', '4', '5', '6', 'X')
 * @return string
 */
function get_raid_bosses($time, $raid_level)
{
    // Get raid level from database
    $rs = my_query(
            '
            SELECT    pokedex_id, pokemon_form_id
            FROM      raid_bosses
            WHERE     \''.$time.'\' BETWEEN date_start AND date_end
            AND       raid_level = \''.$raid_level.'\'
        ');
    debug_log('Checking active raid bosses for raid level '.$raid_level.' at '.$time.':');
    $raid_bosses = [];
    $egg_found = false;
    while ($result = $rs->fetch()) {
        $raid_bosses[] = $result;
        if($result['pokedex_id'] == '999'.$raid_level) $egg_found = true;
        debug_log('Pokedex id: '.$result['pokedex_id'].' | Form id: '.$result['pokemon_form_id']);
    }
    if(!$egg_found) $raid_bosses[] = ['pokedex_id' => '999'.$raid_level, 'pokemon_form_id' => 0]; // Add egg if it wasn't found from db
    return $raid_bosses;
}

?>
