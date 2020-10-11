<?php
/**
 * Raid edit gym keys with active raids marker.
 * @param $first
 * @param $warn
 * @param $action
 * @param $delete
 * @param $hidden
 * @return array
 */
function raid_edit_gym_keys($first, $warn = true, $action = 'edit_raidlevel', $delete = false, $hidden = false)
{
    global $config;
    // Length of first letter.
    // Fix chinese chars, prior: $first_length = strlen($first);
    $first_length = strlen(utf8_decode($first));

    // Special/Custom gym letters?
    $not = '';
    if(!empty($config->RAID_CUSTOM_GYM_LETTERS) && $first_length == 1) {
        // Explode special letters.
        $special_keys = explode(',', $config->RAID_CUSTOM_GYM_LETTERS);

        foreach($special_keys as $id => $letter)
        {
            $letter = trim($letter);
            debug_log($letter, 'Special gym letter:');
            // Fix chinese chars, prior: $length = strlen($letter);
            $length = strlen(utf8_decode($letter));
            $not .= SP . "AND UPPER(LEFT(gym_name, " . $length . ")) != UPPER('" . $letter . "')" . SP;
        }
    }

    // Show hidden gyms?
    if($hidden == true) {
        $show_gym = 0;
    } else {
        $show_gym = 1;
    }

    // Exclude ex-raids?
    $exraid_exclude = '';
    if($config->RAID_EXCLUDE_EXRAID_DUPLICATION) {
        $exraid_exclude = "pokemon.raid_level <> 'X' AND ";
    }

    // Get gyms from database
    $rs = my_query(
        "
        SELECT    gyms.id, gyms.gym_name, gyms.ex_gym,
                  CASE WHEN SUM($exraid_exclude raids.end_time > UTC_TIMESTAMP() - INTERVAL 10 MINUTE) THEN 1 ELSE 0 END AS active_raid
        FROM      gyms
        LEFT JOIN raids
        ON        raids.gym_id = gyms.id
        LEFT JOIN pokemon
        ON        raids.pokemon = pokemon.pokedex_id
        AND       raids.pokemon_form  = pokemon.pokemon_form_id
        WHERE     UPPER(LEFT(gym_name, $first_length)) = UPPER('{$first}')
        $not
        AND       gyms.show_gym = {$show_gym}
        GROUP BY  gym_name, raids.gym_id, gyms.id, gyms.ex_gym
        ORDER BY  gym_name
        "
    );

    // Init empty keys array.
    $keys = [];

    while ($gym = $rs->fetch()) {
        // Add delete argument to keys
        if ($delete == true) {
           $arg = $gym['id'] . '-delete';
        } else {
           $arg = $gym['id'];
        }

        // Write to log.
        // debug_log($gym);

        // No active raid OR only active ex-raid
        if($gym['active_raid'] == 0 || $warn = false) {
            // Show Ex-Gym-Marker?
            if($config->RAID_CREATION_EX_GYM_MARKER && $gym['ex_gym'] == 1) {
                $ex_raid_gym_marker = (strtolower($config->RAID_EX_GYM_MARKER) == 'icon') ? EMOJI_STAR : RAID_EX_GYM_MARKER;
                $gym_name = $ex_raid_gym_marker . SP . $gym['gym_name'];
            } else {
                $gym_name = $gym['gym_name'];
            }

            $keys[] = array(
                'text'          => $gym_name,
                'callback_data' => $first . ':' . $action . ':' . $arg
            );
        }
        // No active raid, but ex raid gym
        else if(($gym['active_raid'] == 0 || $warn = false) && $gym['ex_gym'] == 1) {
            $keys[] = array(
                'text'          => EMOJI_STAR . SP . $gym['gym_name'],
                'callback_data' => $first . ':' . $action . ':' . $arg
            );
        }
        // Add warning emoji for active raid and no ex raid gym
        else if ($gym['active_raid'] == 1 && $gym['ex_gym'] == 0) {
            $keys[] = array(
                'text'          => EMOJI_WARN . SP . $gym['gym_name'],
                'callback_data' => $first . ':' . $action . ':' . $arg
            );
        }
        // Add warning emoji for active raid and ex raid gym
        else if ($gym['active_raid'] == 1 && $gym['ex_gym'] == 1) {
            $keys[] = array(
                'text'          => EMOJI_WARN . SP . EMOJI_STAR . SP . $gym['gym_name'],
                'callback_data' => $first . ':' . $action . ':' . $arg
            );
        }
    }

    // Get the inline key array.
    $keys = inline_key_array($keys, 1);

    return $keys;

}

?>
