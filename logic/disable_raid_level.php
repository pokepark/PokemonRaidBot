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
            DELETE FROM raid_bosses
            WHERE       raid_level IN ({$id})
            AND         date_start = '1970-01-01 00:00:01'
            AND         date_end = '2038-01-19 03:14:07'
            "
        );
}

?>
