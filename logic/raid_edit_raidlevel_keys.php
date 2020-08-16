<?php
/**
 * Raid edit start keys.
 * @param $gym_id
 * @param $gym_first_letter
 * @param $admin
 * @return array
 */
function raid_edit_raidlevel_keys($gym_id, $gym_first_letter, $admin = false)
{
    global $config;
    // Get all raid levels from database
    $rs = my_query(
            "
            SELECT    raid_level, COUNT(*) AS raid_level_count
            FROM      pokemon
            WHERE     raid_level != '0'
            GROUP BY  raid_level
            ORDER BY  FIELD(raid_level, '5', '4', '3', '2', '1', 'X')
            "
        );

    // Init empty keys array.
    $keys = [];

    // Add key for each raid level
    while ($level = $rs->fetch()) {
        // Continue if user is not part of the $config->BOT_ADMINS and raid_level is X
        if($level['raid_level'] == 'X' && $admin === false) continue;

        // Add key for pokemon if we have just 1 pokemon for a level
        if($level['raid_level_count'] == 1) {
            // Raid level and aciton
            $raid_level = $level['raid_level'];

            // Get pokemon from database
            $rs_rl = my_query(
                "
                SELECT    pokedex_id, pokemon_form_id
                FROM      pokemon
                WHERE     raid_level = '{$raid_level}'
                "
            );

            // Add key for pokemon
            while ($pokemon = $rs_rl->fetch()) {
                $keys[] = array(
                    'text'          => get_local_pokemon_name($pokemon['pokedex_id'], $pokemon['pokemon_form_id']),
                    'callback_data' => $gym_id . ',' . $gym_first_letter . ':edit_starttime:' . $pokemon['pokedex_id'] . '-' . $pokemon['pokemon_form_id']
                );
            }
        } else {
            // Add key for raid level
            $keys[] = array(
                'text'          => getTranslation($level['raid_level'] . 'stars'),
                'callback_data' => $gym_id . ',' . $gym_first_letter . ':edit_pokemon:' . $level['raid_level']
            );
        }
    }

    // Get the inline key array.
    $keys = inline_key_array($keys, 3);

    return $keys;
}

?>
