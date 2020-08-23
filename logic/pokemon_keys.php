<?php
/**
 * Pokemon keys.
 * @param $gym_id_plus_letter
 * @param $raid_level
 * @return array
 */
function pokemon_keys($gym_id_plus_letter, $raid_level, $action)
{
    // Init empty keys array.
    $keys = [];

    // Get pokemon from database
    $rs = my_query(
            "
            SELECT    pokedex_id, pokemon_form_id
            FROM      pokemon
            WHERE     raid_level = '$raid_level'
            "
        );

    // Add key for each raid level
    while ($pokemon = $rs->fetch()) {
        $keys[] = array(
            'text'          => get_local_pokemon_name($pokemon['pokedex_id'], $pokemon['pokemon_form_id']),
            'callback_data' => $gym_id_plus_letter . ':' . $action . ':' . $pokemon['pokedex_id'] . '-' . $pokemon['pokemon_form_id']
        );
    }

    // Get the inline key array.
    $keys = inline_key_array($keys, 3);

    return $keys;
}

?>
