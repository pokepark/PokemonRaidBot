<?php
/**
 * Get gym by telegram id.
 * @param $id
 * @return array
 */
function get_gym_by_telegram_id($id)
{
    // Get gym from database
    $rs = my_query(
            "
            SELECT    *
            FROM      gyms
            WHERE     gym_name = '{$id}'
            ORDER BY  id DESC
            LIMIT     1
            "
        );

    $gym = $rs->fetch_assoc();

    return $gym;
}

?>
