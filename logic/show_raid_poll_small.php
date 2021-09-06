<?php
/**
 * Show small raid poll.
 * @param $raid
 * @param $override_language
 * @return string
 */
function show_raid_poll_small($raid, $override_language = false)
{
    // Build message string.
    $msg = '';

    // Gym Name
    if(!empty($raid['gym_name'])) {
        $msg .= '<b>' . $raid['gym_name'] . '</b>' . CR;
    }

    // Address found.
    if (!empty($raid['address'])) {
        $msg .= '<i>' . $raid['address'] . '</i>' . CR2;
    }

    // Pokemon
    if(!empty($raid['pokemon'])) {
        $msg .= '<b>' . get_local_pokemon_name($raid['pokemon'], $raid['pokemon_form']) . '</b> ' . CR;
    }
    // Start time and end time
    if(!empty($raid['start_time']) && !empty($raid['end_time'])) {
        // Get raid times message.
        $msg .= get_raid_times($raid, $override_language);
    }

    // Count attendances
    $rs = my_query(
        "
        SELECT          count(attend_time)          AS count,
                        sum(remote = 0)             AS count_in_person,
                        sum(remote = 1)             AS count_remote,
                        sum(extra_alien)           AS extra_alien
        FROM            attendance
        LEFT JOIN       users
          ON            attendance.user_id = users.user_id
          WHERE         raid_id = {$raid['id']}
            AND         attend_time IS NOT NULL
            AND         raid_done != 1
            AND         cancel != 1
        "
    );

    $row = $rs->fetch();

    // Add to message.
    if ($row['count'] > 0) {
        // Count by team.
        $count_in_person = $row['count_in_person'];
        $count_remote = $row['count_remote'];
        $extra_alien = $row['extra_alien'];

        // Add to message.
        $msg .= EMOJI_GROUP . '<b> ' . ($row['count'] + $row['count_in_person'] + $row['count_remote'] + $row['extra_alien']) . '</b> — ';
        $msg .= (($count_in_person > 0) ? EMOJI_IN_PERSON . $count_in_person . '  ' : '');
        $msg .= (($count_remote > 0) ? EMOJI_REMOTE . $count_remote . '  ' : '');
        $msg .= (($extra_alien > 0) ? EMOJI_ALIEN . $extra_alien . '  ' : '');
        $msg .= CR;
    } else {
        $msg .= getTranslation('no_participants') . CR;
    }

    return $msg;
}

?>
