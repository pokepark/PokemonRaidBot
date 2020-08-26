<?php
/**
 * Get last 50 active raids.
 * @return array
 */
function get_active_raids()
{
    // Get last 50 active raids data.
    $rs = my_query(
        "
        SELECT     raids.*,
                   gyms.lat, gyms.lon, gyms.address, gyms.gym_name, gyms.ex_gym, gyms.gym_note,
                   start_time, end_time,
                   TIME_FORMAT(TIMEDIFF(end_time, UTC_TIMESTAMP()) + INTERVAL 1 MINUTE, '%k:%i') AS t_left
        FROM       raids
        LEFT JOIN  gyms
        ON         raids.gym_id = gyms.id
        WHERE      end_time>UTC_TIMESTAMP()
        ORDER BY   end_time ASC LIMIT 50
        "
    );

    // Get the raids.
    $raids = $rs->fetch();

    debug_log($raids);

    return $raids;
}

?>
