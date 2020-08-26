<?php
/**
 * Get raid time message.
 * @param $raid
 * @param override_language
 * @param pokemon
 * @param unformatted
 * @return string
 */
function get_raid_times($raid, $override_language = true, $unformatted = false)
{
    global $config;
    // Get translation type
    if($override_language == true) {
        $getTypeTranslation = 'getPublicTranslation';
    } else {
        $getTypeTranslation = 'getTranslation';
    }

    // Init empty message string.
    $msg = '';

    // Now
    $week_now = utcnow('W');
    $year_now = utcnow('Y');

    // Start
    $week_start = utctime($raid['start_time'], 'W');
    $weekday_start = utctime($raid['start_time'], 'N');
    $day_start = utctime($raid['start_time'], 'j');
    $month_start = utctime($raid['start_time'], 'm');
    $year_start = utctime($raid['start_time'], 'Y');

    // Translation for raid day and month
    $raid_day = $getTypeTranslation('weekday_' . $weekday_start);
    $raid_month = $getTypeTranslation('month_' . $month_start);

    // Days until the raid starts
    $dt_now = utcdate('now');
    $dt_raid = utcdate($raid['start_time']);
    $date_now = new DateTime($dt_now, new DateTimeZone('UTC'));
    $date_raid = new DateTime($dt_raid, new DateTimeZone('UTC'));
    $days_to_raid = $date_raid->diff($date_now)->format("%a");

    // Raid times.
    if($unformatted == false) {
        if($config->RAID_POLL_POKEMON_NAME_FIRST_LINE == true) {
            $msg .= get_local_pokemon_name($raid['pokemon'], $raid['pokemon_form'], $override_language) . ':' . SP;
        } else {
            $msg .= $getTypeTranslation('raid') . ':' . SP;
        }
    }
    // Is the raid in the same week?
    if($week_now == $week_start && $date_now == $date_raid) {
        // Output: Raid egg opens up 17:00
        if($unformatted == false) {
            $msg .= '<b>';
        }
        $msg .= dt2time($raid['start_time']);
    } else {
        if($days_to_raid > 6) {
        // Output: Raid egg opens on Friday, 13. April (2018) at 17:00
            if($unformatted == false) {
                $msg .= '<b>';
            }
            $msg .= $raid_day . ', ' . $day_start . '. ' . $raid_month . (($year_start > $year_now) ? $year_start : '');

            // Adds 'at 17:00' to the output.
            if($unformatted == false) {
                $msg .= SP . $getTypeTranslation('raid_egg_opens_at');
            }
            $msg .= SP . dt2time($raid['start_time']);
        } else {
            // Output: Raid egg opens on Friday, 17:00
            if($unformatted == false) {
                $msg .= '<b>';
            }
            $msg .= $raid_day;
            $msg .= ', ' . dt2time($raid['start_time']);
        }
    }
    // Add endtime
    //$msg .= SP . $getTypeTranslation('to') . SP . dt2time($raid['end_time']);
    $msg .= SP . '-' . SP . dt2time($raid['end_time']);
    if($unformatted == false) {
        $msg .= '</b>';
    }
    $msg .= CR;

    return $msg;
}

?>
