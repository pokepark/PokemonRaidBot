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
    // Init empty keys array.
    $keys = [];

    // Get pokemon from database
    $rs = my_query(
            "
            SELECT    id, pokedex_id, pokemon_form_id
            FROM      pokemon
            WHERE     raid_level = '{$raid_level}'
            ORDER BY pokedex_id
            "
        );

    // Add key for each raid level
    while ($pokemon = $rs->fetch()) {
        $keys[] = array(
            'text'          => get_local_pokemon_name($pokemon['pokedex_id'], $pokemon['pokemon_form_id']),
            'callback_data' => $gym_id_plus_letter . ':' . $action . ':' . (($event_id!==false) ? $event_id . ',' . $raid_level . ',' : '') . $pokemon['id']
        );
    }

    // Get the inline key array.
    $keys = inline_key_array($keys, 1);

    return $keys;
}

?>
