<?php
/**
 * Raid gym first letter selection
 * @param string $action
 * @param bool $hidden
 * @param int|false $gymarea_id
 * @return array
 */
function raid_edit_gyms_first_letter_keys($action = 'raid_by_gym', $hidden = false, $gymarea_id = false, $gymarea_action = '')
{
    global $config;
    $gymarea_query = $gymarea_name = '';
    $gymarea_keys = [];
    $skip_letter_keys = true;
    if($config->ENABLE_GYM_AREAS) {
        $json = json_decode(file_get_contents(CONFIG_PATH . '/geoconfig.json'),1);
        $points = [];
        foreach($json as $area) {
            if($config->DEFAULT_GYM_AREA != false or $gymarea_id != false) {
                $gymarea_id = ($gymarea_id != false) ? $gymarea_id : $config->DEFAULT_GYM_AREA;
                if($gymarea_id == $area['id']) {
                    foreach($area['path'] as $point) {
                        $points[] = $point[0].' '.$point[1];
                    }
                    $gymarea_name = $area['name'];
                    if($points[0] != $points[count($points)-1]) $points[] = $points[0];
                } else {
                    $gymarea_keys[] = [
                        'text'          => $area['name'],
                        'callback_data' => $area['id'] . ':' . $gymarea_action . ':' . $action
                    ];
                }
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
            foreach($special_keys as $id => $letter)
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

        $rs = my_query(
                "
                {$select}
                FROM gyms
                {$query_condition}
                {$gymarea_query}
                {$group_order}
                "
            );

        while ($gym = $rs->fetch()) {
        // Add first letter to keys array
            $keys[] = array(
                'text'          => $gym['first_letter'],
                'callback_data' => $show_gym . ':' . $action . ':' . $gym['first_letter'] . (($gymarea_id) ? ',' .$gymarea_id : 'false')
            );
        }

        // Get the inline key array.
        $keys = inline_key_array($keys, 4);
    }

    // Add back navigation key.
    if($hidden == false) {
        $nav_keys = [];
        if(!empty($gymarea_keys)) $keys = array_merge($keys, inline_key_array($gymarea_keys, 2));
        $nav_keys[] = universal_inner_key($keys, '0', 'exit', '0', getTranslation('abort'));

        // Get the inline key array.
        $keys[] = $nav_keys;
    }

    return ['keys' => $keys, 'gymarea_name' => $gymarea_name];
}

?>
