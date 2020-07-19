<?php
/**
 * Delete gym.
 * @param $id
 * @return array
 */
function delete_gym($id)
{
    // Get gym from database
    $rs = my_query(
            "
            DELETE FROM gyms
	    WHERE     id = {$id}
            "
        );
}

?>
