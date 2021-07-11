<?php
/**
 * Raid edit start keys.
 * @param $gym_id
 * @param $gym_first_letter
 * @param $admin_access, [ex_raid, event_raid]
 * @param $event_id
 * @return array
 */
function raid_edit_raidlevel_keys($gym_id, $gym_first_letter, $admin_access = [false,false], $event = false)
{
    global $config;

    $time_now = dt2time(utcnow(), 'Y-m-d H:i');

    if($event === false) {
        // Set event ID to null if no event was selected
        $event_id = 'N';
        $query_event = 'AND raid_bosses.raid_level != \'X\'';
    }else {
        $event_id = $event;
        $query_event = '';
    }
    $query = '
        SELECT    pokemon.id, pokemon.pokedex_id, pokemon.pokemon_form_id, raid_bosses.raid_level, COUNT(*) AS raid_level_count
        FROM      raid_bosses
        LEFT JOIN pokemon
        ON        pokemon.pokedex_id = raid_bosses.pokedex_id
        AND       pokemon.pokemon_form_id = raid_bosses.pokemon_form_id
        WHERE     (
                  DATE_SUB(\'' . $time_now . '\', INTERVAL '.$config->RAID_EGG_DURATION.' MINUTE) > date_start 
            OR    DATE_ADD(\'' . $time_now . '\', INTERVAL '.$config->RAID_DURATION.' MINUTE) < date_end
            )
            '.$query_event.'
        GROUP BY  raid_bosses.raid_level
        ORDER BY  FIELD(raid_bosses.raid_level, \'6\', \'5\', \'4\', \'3\', \'2\', \'1\', \'X\')
        ';
    // Get all raid levels from database
    $rs = my_query($query);

    // Init empty keys array.
    $keys = [];

    // Add key for each raid level
    while ($level = $rs->fetch()) {
        // Raid level and action
        $raid_level = $level['raid_level'];

        // Continue if user doesn't have permission to create ex-raid and raid_level is X
        if($raid_level == 'X' && $admin_access[0] === false) continue;

        // Add key for pokemon if we have just 1 pokemon for a level
        if($level['raid_level_count'] == 1) {
            // Add key for pokemon
            $keys[] = array(
                'text'          => get_local_pokemon_name($level['pokedex_id'], $level['pokemon_form_id']),
                'callback_data' => $gym_id . ',' . $gym_first_letter . ':edit_starttime:' . $event_id . ',' . $raid_level . ',' . $level['id']
            );
        } else {
            // Add key for raid level
            $keys[] = array(
                'text'          => getTranslation($raid_level . 'stars'),
                'callback_data' => $gym_id . ',' . $gym_first_letter . ':edit_pokemon:' . $event_id . ',' . $raid_level
            );
        }
    }
    // Add key for raid event if user allowed to create event raids
    if($admin_access[1] === true && $event === false) {
        $keys[] = array(
            'text'          => getTranslation('event'),
            'callback_data' => $gym_id . ',' . $gym_first_letter . ':edit_event:0'
        );
    }
    
    // Get the inline key array.
    $keys = inline_key_array($keys, 3);

    return $keys;
}

?>
