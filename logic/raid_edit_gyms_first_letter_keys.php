<?php
/**
 * Raid gym first letter selection
 * @param string $action Action that is performed by gym letter keys
 * @param bool $hidden Show only hidden gyms?
 * @param int|false $gymarea_id
 * @param string|false $gymarea_action Action that is performed by gym area keys
 * @param string|false $gym_name_action Action that is performed by gym name keys
 * @return array
 */
function raid_edit_gyms_first_letter_keys($action = 'raid_by_gym', $hidden = false, $gymarea_id = false, $gymarea_action = '', $gym_name_action = 'edit_raidlevel')
{
    global $config;
    $gymarea_query = $gymarea_name = '';
    $gymarea_keys = [];
    $skip_letter_keys = true;
    $letters = false;
    if($config->ENABLE_GYM_AREAS) {
        $json = json_decode(file_get_contents(CONFIG_PATH . '/geoconfig_gym_areas.json'),1);
        $points = [];
        foreach($json as $area) {
            $gymarea_id = ($gymarea_id != false) ? $gymarea_id : $config->DEFAULT_GYM_AREA;
            if($gymarea_id != false && $gymarea_id == $area['id']) {
                foreach($area['path'] as $point) {
                    $points[] = $point[0].' '.$point[1];
                }
                $gymarea_name = $area['name'];
                if($points[0] != $points[count($points)-1]) $points[] = $points[0];
                $skip_letter_keys = false;
            } else {
                $gymarea_keys[] = [
                    'text'          => $area['name'],
                    'callback_data' => $area['id'] . ':' . $gymarea_action . ':' . $action
                ];
            }
        }
        $polygon_string = implode(',', $points);
        $gymarea_query = "AND ST_CONTAINS(ST_GEOMFROMTEXT('POLYGON((".$polygon_string."))'), ST_GEOMFROMTEXT(CONCAT('POINT(',lat,' ',lon,')')))";
    }
    // Init empty keys array.
    $keys = [];

    if(!$skip_letter_keys or !$config->ENABLE_GYM_AREAS or $hidden) {
        // Special/Custom gym letters?
        if(!empty($config->RAID_CUSTOM_GYM_LETTERS)) {
            // Explode special letters.
            $special_keys = explode(',', $config->RAID_CUSTOM_GYM_LETTERS);
            $select = 'SELECT CASE ';
            foreach($special_keys as $letter)
            {
                $letter = trim($letter);
                debug_log($letter, 'Special gym letter:');
                // Fix chinese chars, prior: $length = strlen($letter);
                $length = strlen(utf8_decode($letter));
                $select .= SP . "WHEN UPPER(LEFT(gym_name, " . $length . ")) = '" . $letter . "' THEN UPPER(LEFT(gym_name, " . $length . "))" . SP;
            }
            $select .= 'ELSE UPPER(LEFT(gym_name, 1)) END AS first_letter';
            $group_order = 'GROUP BY 1 ORDER BY gym_name';
        }else {
            $select = 'SELECT DISTINCT UPPER(SUBSTR(gym_name, 1, 1)) AS first_letter';
            $group_order = 'ORDER BY 1';
        }
        // Show hidden gyms?
        $show_gym = $hidden ? 0 : 1;

        if($action == 'list_by_gym') {
            // Select only gyms with active raids
            $query_condition = '
            LEFT JOIN raids
            ON        raids.gym_id = gyms.id
            WHERE     end_time > UTC_TIMESTAMP()
            AND       show_gym = ' . $show_gym;
        }else {
            $query_condition = 'WHERE show_gym = ' . $show_gym;
        }

        $rs_count = my_query("SELECT COUNT(gym_name) as count FROM gyms {$query_condition} {$gymarea_query}");
        $gym_count = $rs_count->fetch();
        $rs = my_query(
                "
                {$select}
                FROM gyms
                {$query_condition}
                {$gymarea_query}
                {$group_order}
                "
            );
        // If found over 20 gyms, print letters
        if($gym_count['count'] > 20) {
            while ($gym = $rs->fetch()) {
            // Add first letter to keys array
                $keys[] = array(
                    'text'          => $gym['first_letter'],
                    'callback_data' => $show_gym . ':' . $action . ':' . $gym['first_letter'] . (($gymarea_id) ? ',' .$gymarea_id : '')
                );
            }

            // Get the inline key array.
            $keys = inline_key_array($keys, 4);
            $letters = true;
        }else {
            // If less than 20 gyms was found, print gym names
            if($action == 'list_by_gym') {
                // Select only gyms with active raids
                $query_condition = '
                WHERE     end_time > UTC_TIMESTAMP()
                AND       show_gym = ' . $show_gym;
            }else {
                $query_condition = 'WHERE show_gym = ' . $show_gym;
            }
            $query_collate = '';
            if($config->MYSQL_SORT_COLLATE != "") {
                $query_collate = "COLLATE " . $config->MYSQL_SORT_COLLATE;
            }
            $rs = my_query(
                "
                SELECT    gyms.id, gyms.gym_name, gyms.ex_gym,
                CASE WHEN SUM(raids.end_time > UTC_TIMESTAMP() - INTERVAL 10 MINUTE) THEN 1 ELSE 0 END AS active_raid
                FROM gyms
                LEFT JOIN raids
                ON        raids.gym_id = gyms.id
                {$query_condition}
                {$gymarea_query}
                GROUP BY  gym_name, raids.gym_id, gyms.id, gyms.ex_gym
                ORDER BY  gym_name " . $query_collate . "
                "
            );
            // Init empty keys array.
            $keys = [];

            while ($gym = $rs->fetch()) {
                if($gym['id'] != NULL) {
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
                        'callback_data' => 'gl' . $gymarea_id . ':' . $gym_name_action . ':' . $gym['id']
                    );
                }
            }

            // Get the inline key array.
            $keys = inline_key_array($keys, 1);
        }
    }

    // Add back navigation key.
    if($hidden == false) {
        if($config->RAID_VIA_LOCATION_FUNCTION == 'remote') {
            $query_remote = my_query('SELECT count(*) as count FROM raids LEFT JOIN gyms on raids.gym_id = gyms.id WHERE raids.end_time > (UTC_TIMESTAMP() - INTERVAL 10 MINUTE) AND temporary_gym = 1');
            if($query_remote->fetch()['count'] > 0) {
                $keys[][] = array(
                    'text'          => getTranslation('remote_raids'),
                    'callback_data' => '0:list_remote_gyms:0'
                );
            }
        }
        $nav_keys = [];
        if(!empty($gymarea_keys) && ($config->DEFAULT_GYM_AREA != false || $gymarea_id == false)) $keys = array_merge($keys, inline_key_array($gymarea_keys, 2));

        // Get the inline key array.
        $keys[] = $nav_keys;
    }

    return ['keys' => $keys, 'gymarea_name' => $gymarea_name, 'letters' => $letters];
}

?>
