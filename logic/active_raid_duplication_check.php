<?php
/**
 * Active raid duplication check.
 * @param $gym_id
 * @return string
 */
function active_raid_duplication_check($gym_id)
{
    global $config;

    // Build query.
    $rs = my_query(
        "
        SELECT id, event
        FROM   raids
        WHERE  end_time > (UTC_TIMESTAMP() - INTERVAL 5 MINUTE)
        AND    gym_id = {$gym_id}
        ORDER BY event IS NOT NULL
        "
    );
    $active = 0;
    while($raid = $rs->fetch()) {
        if($config->RAID_EXCLUDE_EXRAID_DUPLICATION && $raid['event'] == EVENT_ID_EX) {
            continue;
        }
        if($config->RAID_EXCLUDE_EVENT_DUPLICATION && $raid['event'] !== NULL && $raid['event'] != EVENT_ID_EX) {
            continue;
        }
        $active = $raid['id'];
        break;
    }
    return $active;
}

?>
