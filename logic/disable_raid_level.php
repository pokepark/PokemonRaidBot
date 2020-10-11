<?php
/**
 * Disable raids for level.
 * @param $id
 * @return array
 */
function disable_raid_level($id)
{
    // Get gym from database
    $rs = my_query(
            "
            UPDATE    pokemon
            SET       raid_level = '0'
            WHERE     raid_level IN ({$id})
            "
        );
}

?>
