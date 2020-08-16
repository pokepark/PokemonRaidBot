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
        SELECT id, pokemon, raid_level, count(gym_id) AS active_raid
        FROM   raids
        WHERE  end_time > (UTC_TIMESTAMP() - INTERVAL 10 MINUTE)
        AND    gym_id = {$gym_id}
        GROUP BY id, pokemon
        "
    );

    // Init counter and raid id.
    $active_counter = 0;
    $active_raid_id = 0;

    // Get row - allow normal and ex-raid at the gym.
    if($config->RAID_EXCLUDE_EXRAID_DUPLICATION) {
        while ($raid = $rs->fetch()) {
            $active = $raid['active_raid'];
            if ($active > 0) {
                // Exclude ex-raid pokemon.
                $raid_level = $raid['raid_level'];
                if($raid_level == 'X') {
                    continue;
                } else {
                    $active_raid_id = $raid['id'];
                    $active_counter = $active_counter + 1;
                    break;
                }
            // No active raids.
            } else {
                break;
            }
        }
    } else {
        $raid = $rs->fetch();
        $active_counter = $raid['active_raid'];
        $active_raid_id = $raid['id'];
   }

    // Return 0 or raid id
    if ($active_counter > 0) {
        return $active_raid_id;
    } else {
        return 0;
    }
}

?>
