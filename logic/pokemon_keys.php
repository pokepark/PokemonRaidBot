<?php
/**
 * Pokemon keys.
 * @param $gym_id_plus_letter
 * @param $raid_level
 * @param $event_id
 * @return array
 */
function pokemon_keys($gym_id_plus_letter, $raid_level, $action, $event_id = false)
{
    global $config;
    // Init empty keys array.
    $keys = [];
    
    $time_now = dt2time(utcnow(), 'Y-m-d H:i');
    // Get pokemon from database
    $query =         '
            SELECT    pokemon.id, pokemon.pokedex_id, pokemon.pokemon_form_id
            FROM      raid_bosses
            LEFT JOIN pokemon
            ON        pokemon.pokedex_id = raid_bosses.pokedex_id
            AND       pokemon.pokemon_form_id = raid_bosses.pokemon_form_id
            WHERE     raid_bosses.raid_level = \'' . $raid_level . '\'
            AND       (
                      DATE_SUB(\'' . $time_now . '\', INTERVAL '.$config->RAID_EGG_DURATION.' MINUTE) between date_start and date_end
                OR    DATE_ADD(\'' . $time_now . '\', INTERVAL '.$config->RAID_DURATION.' MINUTE) between date_start and date_end
                )
            ORDER BY  pokemon.pokedex_id
            ';
    $rs = my_query($query);
    $egg_insert = true;
    // Add key for each raid level
    while ($pokemon = $rs->fetch()) {
        $keys[] = array(
            'text'          => get_local_pokemon_name($pokemon['pokedex_id'], $pokemon['pokemon_form_id']),
            'callback_data' => $gym_id_plus_letter . ':' . $action . ':' . (($event_id!==false) ? $event_id . ',' . $raid_level . ',' : '') . $pokemon['id']
        );
        if($pokemon['pokedex_id'] > 9990) $egg_insert = false;
    }
    if($egg_insert) {
        $egg_id = '999' . $raid_level;
        $keys[] = array(
            'text'          => get_local_pokemon_name($egg_id, 0),
            'callback_data' => $gym_id_plus_letter . ':' . $action . ':' . (($event_id!==false) ? $event_id . ',' . $raid_level . ',' : '') . $egg_id
        );
    }

    // Get the inline key array.
    $keys = inline_key_array($keys, 1);

    return $keys;
}

?>
