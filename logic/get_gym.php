<?php
/**
 * Get gym.
 * @param $id
 * @return array
 */
function get_gym($id)
{
    // Get gym from database
    $rs = my_query(
            "
            SELECT    *
            FROM      gyms
	    WHERE     id = {$id}
            "
        );

    $gym = $rs->fetch_assoc();

    return $gym;
}

?>
