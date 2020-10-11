<?php
/**
 * Get gyms by searchterm.
 * @param $searchterm
 * @return bool|array
 */
function raid_get_gyms_list_keys($searchterm)
{
    // Init empty keys array.
    $keys = [];

    // Make sure the search term is not empty
    if(!empty($searchterm)) {
        // Get gyms from database
        $rs = my_query(
                "
                SELECT    id, gym_name
                FROM      gyms
                WHERE     gym_name LIKE '$searchterm%'
		AND       show_gym LIKE 1
                OR        gym_name LIKE '%$searchterm%'
		AND       show_gym LIKE 1
                ORDER BY
                  CASE
                    WHEN  gym_name LIKE '$searchterm%' THEN 1
                    WHEN  gym_name LIKE '%$searchterm%' THEN 2
                    ELSE  3
                  END
                LIMIT     15
                "
            );

        while ($gym = $rs->fetch()) {
            $first = strtoupper(substr($gym['gym_name'], 0, 1));
	    $keys[] = array(
                'text'          => $gym['gym_name'],
                'callback_data' => $first . ':edit_raidlevel:' . $gym['id']
            );
        }
    }

    // Add abort key.
    if($keys) {
        // Get the inline key array.
        $keys = inline_key_array($keys, 1);

        // Add back navigation key.
        $nav_keys = [];
        $nav_keys[] = universal_inner_key($keys, '0', 'exit', '0', getTranslation('abort'));

        // Get the inline key array.
        $keys[] = $nav_keys;
    }

    return $keys;
}


?>
