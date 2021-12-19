<?php
/**
 * Disable raids for level.
 * @param $id
 * @return array
 */
function disable_raid_level($id)
{
    // Get gym from database
    my_query(
            "
            DELETE FROM raid_bosses
            WHERE       raid_level IN ({$id})
            AND         scheduled = 0
            "
        );
}

?>
