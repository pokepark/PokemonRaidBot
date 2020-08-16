<?php
/**
 * Get raid data.
 * @param $raid_id
 * @return array
 */
function get_raid($raid_id)
{
    // Get the raid data by id.
    $rs = my_query(
        "
        SELECT     raids.*,
                   gyms.lat, gyms.lon, gyms.address, gyms.gym_name, gyms.ex_gym, gyms.gym_note,
                   users.name,
                   TIME_FORMAT(TIMEDIFF(end_time, UTC_TIMESTAMP()) + INTERVAL 1 MINUTE, '%k:%i') AS t_left,
                   TIMESTAMPDIFF(MINUTE,raids.start_time,raids.end_time) as t_duration
        FROM       raids
        LEFT JOIN  gyms
        ON         raids.gym_id = gyms.id
        LEFT JOIN  users
        ON         raids.user_id = users.user_id
        WHERE      raids.id = {$raid_id}
        "
    );

    // Get the row.
    $raid = $rs->fetch();
    debug_log($raid);

    return $raid;
}

?>
