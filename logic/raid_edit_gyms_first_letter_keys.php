<?php
/**
 * Raid gym first letter selection
 * @param $action
 * @param $hidden
 * @return array
 */
function raid_edit_gyms_first_letter_keys($action = 'raid_by_gym', $hidden = false)
{
    global $config;
    // Special/Custom gym letters?
    $case = '';
    if(!empty($config->RAID_CUSTOM_GYM_LETTERS)) {
        // Explode special letters.
        $special_keys = explode(',', $config->RAID_CUSTOM_GYM_LETTERS);
        foreach($special_keys as $id => $letter)
        {
            $letter = trim($letter);
            debug_log($letter, 'Special gym letter:');
            // Fix chinese chars, prior: $length = strlen($letter);
            $length = strlen(utf8_decode($letter));
            $case .= SP . "WHEN UPPER(LEFT(gym_name, " . $length . ")) = '" . $letter . "' THEN UPPER(LEFT(gym_name, " . $length . "))" . SP;
        }
    }

    // Show hidden gyms?
    if($hidden == true) {
        $show_gym = 0;
    } else {
        $show_gym = 1;
    }

    // Case or not?
    if(!empty($case)) {
        // List or other action?
        if($action == 'list_by_gym') {
            // Get gyms with active raids only from database
            $rs = my_query(
                    "
                    SELECT CASE $case
                    ELSE UPPER(LEFT(gym_name, 1))
                    END       AS first_letter
                    FROM      raids
                    LEFT JOIN gyms 
                    ON        raids.gym_id = gyms.id 
                    WHERE     end_time>UTC_TIMESTAMP() 
                    AND       show_gym = {$show_gym}
                    GROUP BY  1
                    ORDER BY  gym_name
                    "
                );
        } else {
            // Get gyms from database
            $rs = my_query(
                    "
                    SELECT CASE $case
                    ELSE UPPER(LEFT(gym_name, 1))
                    END       AS first_letter
                    FROM      gyms
                    WHERE     show_gym = {$show_gym}
                    GROUP BY  1
                    ORDER BY  gym_name
                    "
                );
        }
    } else {
        // List or other action?
        if($action == 'list_by_gym') {
            // Get gyms with active raids only from database
            // Get gyms from database
            $rs = my_query(
                    "
                    SELECT DISTINCT UPPER(SUBSTR(gym_name, 1, 1)) AS first_letter
                    FROM      raids
                    LEFT JOIN gyms 
                    ON        raids.gym_id = gyms.id 
                    WHERE     end_time>UTC_TIMESTAMP() 
                    AND       show_gym = {$show_gym}
                    ORDER BY 1
                    "
                );
        } else {
            // Get gyms from database
            $rs = my_query(
                    "
                    SELECT DISTINCT UPPER(SUBSTR(gym_name, 1, 1)) AS first_letter
                    FROM      gyms
                    WHERE     show_gym = {$show_gym}
                    ORDER BY 1
                    "
                );
        }
    }

    // Init empty keys array.
    $keys = [];

    while ($gym = $rs->fetch()) {
	// Add first letter to keys array
        $keys[] = array(
            'text'          => $gym['first_letter'],
            'callback_data' => $show_gym . ':' . $action . ':' . $gym['first_letter']
        );
    }

    // Get the inline key array.
    $keys = inline_key_array($keys, 4);

    // Add back navigation key.
    if($hidden == false) {
        $nav_keys = [];
        $nav_keys[] = universal_inner_key($keys, '0', 'exit', '0', getTranslation('abort'));

        // Get the inline key array.
        $keys[] = $nav_keys;
    }

    return $keys;
}

?>
