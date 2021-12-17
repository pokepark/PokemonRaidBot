<?php
/**
 * Raid edit gym keys with active raids marker.
 * @param $first
 * @param $gymarea_id
 * @param $action
 * @param $delete
 * @param $hidden
 * @return array
 */
function raid_edit_gym_keys($first, $gymarea_id = false, $action = 'edit_raidlevel', $delete = false, $hidden = false)
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
    $gymarea_query = '';
    if($gymarea_id != false) {
        $json = json_decode(file_get_contents(CONFIG_PATH . '/geoconfig_gym_areas.json'),1);
        $points = [];
        foreach($json as $area) {
            if($gymarea_id == $area['id']) {
                foreach($area['path'] as $point) {
                    $points[] = $point[0].' '.$point[1];
                }
                if($points[0] != $points[count($points)-1]) $points[] = $points[0];
                break;
            }
        }
        $polygon_string = implode(',', $points);
        $gymarea_query = "AND ST_CONTAINS(ST_GEOMFROMTEXT('POLYGON((".$polygon_string."))'), ST_GEOMFROMTEXT(CONCAT('POINT(',lat,' ',lon,')')))";
   }
    // Show hidden gyms?
    if($hidden == true) {
        $show_gym = 0;
    } else {
        $show_gym = 1;
    }
    $query_collate = "";
    if($config->MYSQL_SORT_COLLATE != "") {
        $query_collate = "COLLATE " . $config->MYSQL_SORT_COLLATE;
    }
    // Get gyms from database
    $rs = my_query(
        "
        SELECT    gyms.id, gyms.gym_name, gyms.ex_gym,
                  CASE WHEN SUM(raids.end_time > UTC_TIMESTAMP() - INTERVAL 10 MINUTE) THEN 1 ELSE 0 END AS active_raid
        FROM      gyms
        LEFT JOIN raids
        ON        raids.gym_id = gyms.id
        WHERE     UPPER(LEFT(gym_name, $first_length)) = UPPER('{$first}')
<<<<<<< HEAD
	$not
	$gymarea_query
=======
        $not
        $gymarea_query
<<<<<<< HEAD
>>>>>>> 974937cda6355f2a888cf0c1ac394214e6980f17
=======
>>>>>>> 974937cda6355f2a888cf0c1ac394214e6980f17
        AND       gyms.show_gym = {$show_gym}
        GROUP BY  gym_name, raids.gym_id, gyms.id, gyms.ex_gym
        ORDER BY  gym_name " . $query_collate . "
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

        // List action to list only gyms with active raids, so always continue at the end
        if ($action == 'list_raid') {
            if ($gym['active_raid'] == 1) {
                $keys[] = array(
                    'text'          => $gym['gym_name'],
                    'callback_data' => $first . ':' . $action . ':' . $arg
                );
            }
            // Continue always in case of list action
            continue;
        }

        // Write to log.
        // debug_log($gym);
        
        $active_raid = active_raid_duplication_check($gym['id']);
        
        // Show Ex-Gym-Marker?
        if($config->RAID_CREATION_EX_GYM_MARKER && $gym['ex_gym'] == 1) {
            $ex_raid_gym_marker = (strtolower($config->RAID_EX_GYM_MARKER) == 'icon') ? EMOJI_STAR : $config->RAID_EX_GYM_MARKER;
            $gym_name = $ex_raid_gym_marker . SP . $gym['gym_name'];
        } else {
            $gym_name = $gym['gym_name'];
        }
        // Add warning emoji for active raid
        if ($active_raid > 0) {
            $gym_name = EMOJI_WARN . SP . $gym_name;
        }
        $keys[] = array(
            'text'          => $gym_name,
            'callback_data' => $first . ':' . $action . ':' . $arg
        );
    }

    // Get the inline key array.
    $keys = inline_key_array($keys, 1);

    return $keys;

}

?>
