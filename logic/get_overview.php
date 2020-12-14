<?php
/**
 * Return the overview message for a specific chat.
 * @param $active_raids - Custom array of gym and raid info
 * @param $chat_id - String
 * @return string
 */
function get_overview( $active_raids, $chat_id )
{
    global $config;

    // Get info about chat for username.
    debug_log('Getting chat object for chat_id: ' . $chat_id);
    $chat_obj = get_chat($chat_id);
    $chat_username = '';

    // Set chat username if available.
    if ($chat_obj['ok'] == 'true' && isset($chat_obj['result']['username'])) {
        $chat_username = $chat_obj['result']['username'];
        debug_log('Username of the chat: ' . $chat_obj['result']['username']);
    }
    $chat_title = get_chat_title($chat_id);
    $msg = '<b>' . getPublicTranslation('raid_overview_for_chat') . ' ' . $chat_title . ' ' . getPublicTranslation('from') . ' '. dt2time('now') . '</b>' .  CR . CR;

    $now = utcnow();

    if(count($active_raids) == 0) {
        $msg .= getPublicTranslation('no_active_raids') . CR . CR;
    }else {
        foreach($active_raids as $row) {
            // Set variables for easier message building.
            $raid_id = $row['id'];
            $pokemon = get_local_pokemon_name($row['pokemon'], $row['pokemon_form'], true);
            $gym = $row['gym_name'];
            $ex_gym = $row['ex_gym'];
            $ex_raid_gym_marker = (strtolower($config->RAID_EX_GYM_MARKER) == 'icon') ? EMOJI_STAR : '<b>' . $config->RAID_EX_GYM_MARKER . '</b>';
            $start_time = $row['start_time'];
            $end_time = $row['end_time'];
            $time_left = $row['t_left'];

            debug_log($pokemon . '@' . $gym . ' found for overview.');
            // Build message and add each gym in this format - link gym_name to raid poll chat_id + message_id if possible
            /* Example:
             * Raid Overview from 18:18h
             *
             * Train Station Gym
             * Raikou - still 0:24h
             *
             * Bus Station Gym
             * Level 5 Egg 18:41 to 19:26
            */
            // Gym name.
            $msg .= $ex_gym ? $ex_raid_gym_marker . SP : '';
            $msg .= !empty($chat_username) ? '<a href="https://t.me/' . $chat_username . '/' . $row['message_id'] . '">' . htmlspecialchars($gym) . '</a>' : $gym;
            $msg .= CR;

            // Raid has not started yet - adjust time left message
            if ($now < $start_time) {
                $msg .= get_raid_times($row, true);
            // Raid has started already
            } else {
                // Add time left message.
                $msg .= $pokemon . ' — <b>' . getPublicTranslation('still') . SP . $time_left . 'h</b>' . CR;
            }
            $exclude_pokemon_sql = "";
            if(!in_array($row['pokemon'], $GLOBALS['eggs'])) {
                $exclude_pokemon_sql = 'AND (pokemon = \''.$row['pokemon'].'-'.$row['pokemon_form'].'\' or pokemon = \'0\')';
            }
            // Count attendances
            $rs_att = my_query(
            "
            SELECT      count(attend_time)                                                                                  AS count,
                        sum(team = 'mystic'     && want_invite = 0) + sum(case when want_invite = 0 then attendance.extra_mystic else 0 end)         AS count_mystic,
                        sum(team = 'valor'      && want_invite = 0) + sum(case when want_invite = 0 then attendance.extra_valor else 0 end)          AS count_valor,
                        sum(team = 'instinct'   && want_invite = 0) + sum(case when want_invite = 0 then attendance.extra_instinct else 0 end)       AS count_instinct,
                        sum(team IS NULL        && want_invite = 0)                                                         AS count_no_team,
                        sum(case when want_invite = 1 then 1+attendance.extra_mystic+extra_instinct+extra_valor else 0 end) AS count_want_invite
            FROM        ( 
                          SELECT DISTINCT attend_time, user_id, extra_mystic, extra_valor, extra_instinct, want_invite
                          FROM attendance
                          WHERE raid_id = {$raid_id}
                          AND attend_time IS NOT NULL
                          AND ( attend_time > UTC_TIMESTAMP() or attend_time = '" . ANYTIME . "' )
                          AND raid_done != 1
                          AND cancel != 1
                          {$exclude_pokemon_sql}
                        ) as attendance
            LEFT JOIN   users
            ON          attendance.user_id = users.user_id
            "
            );

            $att = $rs_att->fetch();

            // Add to message.
            if ($att['count'] > 0) {
                $msg .= EMOJI_GROUP . '<b> ' . ($att['count_mystic'] + $att['count_valor'] + $att['count_instinct'] + $att['count_no_team'] + $att['count_want_invite']) . '</b> — ';
                $msg .= ((($att['count_mystic']) > 0) ? TEAM_B . ($att['count_mystic']) . '  ' : '');
                $msg .= ((($att['count_valor']) > 0) ? TEAM_R . ($att['count_valor']) . '  ' : '');
                $msg .= ((($att['count_instinct']) > 0) ? TEAM_Y . ($att['count_instinct']) . '  ' : '');
                $msg .= (($att['count_no_team'] > 0) ? TEAM_UNKNOWN . $att['count_no_team'] : '');
                $msg .= (($att['count_want_invite'] > 0) ? EMOJI_WANT_INVITE . $att['count_want_invite'] : '');
                $msg .= CR;
            }
            $msg .= CR;
        }
    }
    //Add custom message from the config.
    if (!empty($config->RAID_PIN_MESSAGE)) {
        $msg .=  $config->RAID_PIN_MESSAGE;
    }
    return $msg;
}
?>
