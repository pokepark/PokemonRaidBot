<?php
/**
 * Get last 12 active raids.
 * @return array
 */
function get_active_raids($event_permissions = false)
{
    // Get last 12 active raids data.
    $rs = my_query(
        "
        SELECT     raids.*,
                   gyms.lat, gyms.lon, gyms.address, gyms.gym_name, gyms.ex_gym, gyms.gym_note,
                   start_time, end_time,
                   TIME_FORMAT(TIMEDIFF(end_time, UTC_TIMESTAMP()) + INTERVAL 1 MINUTE, '%k:%i') AS t_left,
                   (SELECT COUNT(*) FROM raids WHERE end_time>UTC_TIMESTAMP()) AS r_active
        FROM       raids
        LEFT JOIN  gyms
        ON         raids.gym_id = gyms.id
        WHERE      end_time>UTC_TIMESTAMP()
        ".($event_permissions?"":"AND event IS NULL")."
        ORDER BY   end_time ASC
        LIMIT      12
        "
    );

    // Get the raids.
    $raids = $rs->fetchAll();

    debug_log($raids);

    return $raids;
}

?>
