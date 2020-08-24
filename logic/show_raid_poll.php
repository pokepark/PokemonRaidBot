<?php
/**
 * Show raid poll.
 * @param $raid
 * @return string
 */
function show_raid_poll($raid)
{
    global $config;
    // Init empty message string.
    //$msg = '';
    $msg = array();

    // Get current pokemon
    $raid_pokemon_id = $raid['pokemon'];
    $raid_pokemon_form = $raid['pokemon_form'];
    $raid_pokemon_form_name = get_pokemon_form_name($raid_pokemon_id, $raid_pokemon_form);
    $raid_pokemon = $raid_pokemon_id . "-" . $raid_pokemon_form;

    // Get raid level
    $raid_level = get_raid_level($raid_pokemon_id, $raid_pokemon_form);

    // Get raid times.
    $msg = raid_poll_message($msg, get_raid_times($raid), true);

    // Get current time and time left.
    $time_now = utcnow();
    $time_left = $raid['t_left'];

    // Display gym details.
    if ($raid['gym_name'] || $raid['gym_team']) {
        // Add gym name to message.
        if ($raid['gym_name']) {
            $ex_raid_gym_marker = (strtolower($config->RAID_EX_GYM_MARKER) == 'icon') ? EMOJI_STAR : '<b>' . $config->RAID_EX_GYM_MARKER . '</b>';
            //$msg .= getPublicTranslation('gym') . ': ' . ($raid['ex_gym'] ? $ex_raid_gym_marker . SP : '') . '<b>' . $raid['gym_name'] . '</b>';
            $msg = raid_poll_message($msg, getPublicTranslation('gym') . ': ' . ($raid['ex_gym'] ? $ex_raid_gym_marker . SP : '') . '<b>' . $raid['gym_name'] . '</b>', true);
        }

        // Add team to message.
        if ($raid['gym_team']) {
            //$msg .= ' ' . $GLOBALS['teams'][$raid['gym_team']];
            $msg = raid_poll_message($msg, SP . $GLOBALS['teams'][$raid['gym_team']], true);
        }

        //$msg .= CR;
        $msg = raid_poll_message($msg, CR, true);
    }

    // Add maps link to message.
    if (!empty($raid['address'])) {
        $msg = raid_poll_message($msg, ($config->RAID_PICTURE ? $raid['gym_name'].': ' : ''). mapslink($raid) . CR);
    } else {
        // Get the address.
        $addr = get_address($raid['lat'], $raid['lon']);
        $address = format_address($addr);

        //Only store address if not empty
        if(!empty($address)) {
            my_query(
	            "
	            UPDATE    gyms
	            SET     address = '{$address}'
	            WHERE   id = {$raid['gym_id']}
	            "
            );
            //Use new address
	    $msg = raid_poll_message($msg, ($config->RAID_PICTURE ? $raid['gym_name'].': ' : ''). mapslink($raid,$address) . CR);
        } else {
            //If no address is found show maps link
            $msg = raid_poll_message($msg, ($config->RAID_PICTURE ? $raid['gym_name'].': ' : ''). mapslink($raid,'1') . CR);
        }
    }

    // Display raid boss name.
    $msg = raid_poll_message($msg, getPublicTranslation('raid_boss') . ': <b>' . get_local_pokemon_name($raid_pokemon_id, $raid['pokemon_form'], true) . '</b>', true);

    // Display raid boss weather.
    $pokemon_weather = get_pokemon_weather($raid_pokemon_id, $raid_pokemon_form);
    $msg = raid_poll_message($msg, ($pokemon_weather != 0) ? (' ' . get_weather_icons($pokemon_weather)) : '', true);
    $msg = raid_poll_message($msg, CR, true);

    // Display attacks.
    if ($raid['move1'] > 1 && $raid['move2'] > 2 ) {
        $msg = raid_poll_message($msg, getPublicTranslation('pokemon_move_' . $raid['move1']) . '/' . getPublicTranslation('pokemon_move_' . $raid['move2']));
        $msg = raid_poll_message($msg, CR);
    }

    // Hide participants?
    if($config->RAID_POLL_HIDE_USERS_TIME > 0) {
        if($config->RAID_ANYTIME) {
            $hide_users_sql = "AND (attend_time > (UTC_TIMESTAMP() - INTERVAL " . $config->RAID_POLL_HIDE_USERS_TIME . " MINUTE) OR attend_time = 0)";
        } else {
            $hide_users_sql = "AND attend_time > (UTC_TIMESTAMP() - INTERVAL " . $config->RAID_POLL_HIDE_USERS_TIME . " MINUTE)";
        }
    } else {
        $hide_users_sql = "";
    }

    // Get counts and sums for the raid
    // 1 - Grouped by attend_time
    $rs_cnt = my_query(
        "
        SELECT DISTINCT DATE_FORMAT(attend_time, '%Y%m%d%H%i%s') AS ts_att,
                        count(attend_time)          AS count,
                        sum(team = 'mystic')        AS count_mystic,
                        sum(team = 'valor')         AS count_valor,
                        sum(team = 'instinct')      AS count_instinct,
                        sum(team IS NULL)           AS count_no_team,
                        sum(extra_mystic)           AS extra_mystic,
                        sum(extra_valor)            AS extra_valor,
                        sum(extra_instinct)         AS extra_instinct,
                        sum(IF(remote = '1', (remote = '1') + extra_mystic + extra_valor + extra_instinct, 0)) AS count_remote,
                        sum(IF(late = '1', (late = '1') + extra_mystic + extra_valor + extra_instinct, 0)) AS count_late,
                        sum(pokemon = '0')                   AS count_any_pokemon,
                        sum(pokemon = '{$raid_pokemon}')  AS count_raid_pokemon,
                        sum(pokemon != '{$raid_pokemon}' AND pokemon != '0')  AS count_other_pokemon,
                        attend_time,
                        pokemon
        FROM            attendance
        LEFT JOIN       users
          ON            attendance.user_id = users.user_id
          WHERE         raid_id = {$raid['id']}
                        $hide_users_sql
            AND         attend_time IS NOT NULL
            AND         raid_done != 1
            AND         cancel != 1
          GROUP BY      attend_time, pokemon
          ORDER BY      attend_time, pokemon
        "
    );

    // Init empty count array and count sum.
    $cnt = [];
    $cnt_all = 0;
    $cnt_latewait = 0;
    $cnt_remote = 0;

    while ($cnt_row = $rs_cnt->fetch()) {
        $cnt[$cnt_row['ts_att']] = $cnt_row;
        $cnt_all = $cnt_all + $cnt_row['count'];
        $cnt_latewait = $cnt_latewait + $cnt_row['count_late'];
        $cnt_remote = $cnt_remote + $cnt_row['count_remote'];
    }

    // Write to log.
    debug_log($cnt);

    // Buttons for raid levels and pokemon hidden?
    $hide_buttons_raid_level = explode(',', $config->RAID_POLL_HIDE_BUTTONS_RAID_LEVEL);
    $hide_buttons_pokemon = explode(',', $config->RAID_POLL_HIDE_BUTTONS_POKEMON);
    $buttons_hidden = false;
    if(in_array($raid_level, $hide_buttons_raid_level) || in_array($raid_pokemon_id, $hide_buttons_pokemon) || in_array($raid_pokemon_id.'-'.$raid_pokemon_form_name, $hide_buttons_pokemon)) {
        $buttons_hidden = true;
    }

    // Raid has started and has participants
    if($time_now > $raid['start_time'] && $cnt_all > 0) {
        // Display raid boss CP values.
        $pokemon_cp = get_formatted_pokemon_cp($raid_pokemon_id, $raid_pokemon_form, true);
        $msg = raid_poll_message($msg, (!empty($pokemon_cp)) ? ($pokemon_cp . CR) : '', true);

        // Add raid is done message.
        if($time_now > $raid['end_time']) {
            $msg = raid_poll_message($msg, '<b>' . getPublicTranslation('raid_done') . '</b>' . CR);

        // Add time left message.
        } else {
            $msg = raid_poll_message($msg, getPublicTranslation('raid') . ' — <b>' . getPublicTranslation('still') . ' ' . $time_left . 'h</b>' . CR);
        }
    // Buttons are hidden?
    } else if($buttons_hidden) {
        // Display raid boss CP values.
        $pokemon_cp = get_formatted_pokemon_cp($raid['pokemon'], true);
        $msg = raid_poll_message($msg, (!empty($pokemon_cp)) ? ($pokemon_cp . CR) : '', true);
    }

    // Hide info if buttons are hidden
    if($buttons_hidden) {
        // Show message that voting is not possible!
        $msg = raid_poll_message($msg, CR . '<b>' . getPublicTranslation('raid_info_no_voting') . '</b> ' . CR);
    } else {
        // Gym note?
        if(!empty($raid['gym_note'])) {
            $msg = raid_poll_message($msg, EMOJI_INFO . SP . $raid['gym_note'] . CR);
        }

        // Add Ex-Raid Message if Pokemon is in Ex-Raid-List.
        if($raid_level == 'X') {
            $msg = raid_poll_message($msg, CR . EMOJI_WARN . ' <b>' . getPublicTranslation('exraid_pass') . '</b> ' . EMOJI_WARN . CR);
        }

        // Add attendances message.
        if ($cnt_all > 0) {
            // Get counts and sums for the raid
            // 2 - Grouped by attend_time and pokemon
            $rs_cnt_pokemon = my_query(
                "
                SELECT DISTINCT DATE_FORMAT(attend_time, '%Y%m%d%H%i%s') AS ts_att,
                                count(attend_time)          AS count,
                                sum(team = 'mystic')        AS count_mystic,
                                sum(team = 'valor')         AS count_valor,
                                sum(team = 'instinct')      AS count_instinct,
                                sum(team IS NULL)           AS count_no_team,
                                sum(extra_mystic)           AS extra_mystic,
                                sum(extra_valor)            AS extra_valor,
                                sum(extra_instinct)         AS extra_instinct,
                                sum(IF(remote = '1', (remote = '1') + extra_mystic + extra_valor + extra_instinct, 0)) AS count_remote,
                                sum(IF(late = '1', (late = '1') + extra_mystic + extra_valor + extra_instinct, 0)) AS count_late,
                                sum(pokemon = '0')                   AS count_any_pokemon,
                                sum(pokemon = '{$raid_pokemon}')  AS count_raid_pokemon,
                                attend_time,
                                pokemon
                FROM            attendance
                LEFT JOIN       users
                  ON            attendance.user_id = users.user_id
                  WHERE         raid_id = {$raid['id']}
                                $hide_users_sql
                    AND         attend_time IS NOT NULL
                    AND         raid_done != 1
                    AND         cancel != 1
                  GROUP BY      attend_time, pokemon
                  ORDER BY      attend_time, pokemon
                "
            );

            // Init empty count array and count sum.
            $cnt_pokemon = [];

            while ($cnt_rowpoke = $rs_cnt_pokemon->fetch()) {
                $cnt_pokemon[$cnt_rowpoke['ts_att'] . '_' . $cnt_rowpoke['pokemon']] = $cnt_rowpoke;
            }

            // Write to log.
            debug_log($cnt_pokemon);

            // Get attendance for this raid.
            $rs_att = my_query(
                "
                SELECT      attendance.*,
                            users.name,
                            users.level,
                            users.team,
                            DATE_FORMAT(attend_time, '%Y%m%d%H%i%s') AS ts_att
                FROM        attendance
                LEFT JOIN   users
                ON          attendance.user_id = users.user_id
                  WHERE     raid_id = {$raid['id']}
                            $hide_users_sql
                    AND     raid_done != 1
                    AND     cancel != 1
                  ORDER BY  attend_time,
                            pokemon,
                            users.team,
                            arrived,
                            users.level desc,
                            users.name
                "
            );

            // Init previous attend time and pokemon
            $previous_att_time = 'FIRST_RUN';
            $previous_pokemon = 'FIRST_RUN';

            // For each attendance.
            while ($row = $rs_att->fetch()) {
                // Set current attend time and pokemon
                $current_att_time = $row['ts_att'];
                $dt_att_time = dt2time($row['attend_time']);
                $current_pokemon = $row['pokemon'];

                $poke = explode("-",$current_pokemon);
                $current_pokemon_id = $poke[0];
                $current_pokemon_form = $poke[1];
                
                // Add hint for remote attendances.
                if($config->RAID_REMOTEPASS_USERS && $previous_att_time == 'FIRST_RUN' && $cnt_remote > 0) {
                    $remote_max_msg = str_replace('REMOTE_MAX_USERS', $config->RAID_REMOTEPASS_USERS_LIMIT, getPublicTranslation('remote_participants_max'));
                    $msg = raid_poll_message($msg, CR . EMOJI_REMOTE . SP . getPublicTranslation('remote_participants') . SP . '<i>' . $remote_max_msg . '</i>' . CR);
                }

                // Add start raid message
                if($previous_att_time == 'FIRST_RUN') {
                    $msg = raid_poll_message($msg, CR . '<b>' . str_replace('START_CODE', '<a href="https://t.me/' . str_replace('@', '', $config->BOT_NAME) . '?start=c0de-' . $raid['id'] . '">' . getTranslation('telegram_bot_start') . '</a>', getPublicTranslation('start_raid')) . '</b>' . SP . '<i>' . getPublicTranslation('start_raid_info') . '</i>' . CR);
                }

                // Add hint for late attendances.
                if($config->RAID_LATE_MSG && $previous_att_time == 'FIRST_RUN' && $cnt_latewait > 0) {
                    $late_wait_msg = str_replace('RAID_LATE_TIME', $config->RAID_LATE_TIME, getPublicTranslation('late_participants_wait'));
                    $msg = raid_poll_message($msg, CR . EMOJI_LATE . '<i>' . getPublicTranslation('late_participants') . ' ' . $late_wait_msg . '</i>' . CR);
                }

                // Add section/header for time
                if($previous_att_time != $current_att_time) {
                    // Add to message.
                    $count_att_time_extrapeople = $cnt[$current_att_time]['extra_mystic'] + $cnt[$current_att_time]['extra_valor'] + $cnt[$current_att_time]['extra_instinct'];
                    $msg = raid_poll_message($msg, CR . '<b>' . (($current_att_time == 0) ? (getPublicTranslation('anytime')) : ($dt_att_time)) . '</b>');

                    // Hide if other pokemon got selected. Show attendances for each pokemon instead of each attend time.
                    $msg = raid_poll_message($msg, (($cnt[$current_att_time]['count_other_pokemon'] == 0) ? (' [' . ($cnt[$current_att_time]['count'] + $count_att_time_extrapeople) . ']') : ''));

                    // Add attendance counts by team - hide if other pokemon got selected.
                    if ($cnt[$current_att_time]['count'] > 0 && $cnt[$current_att_time]['count_other_pokemon'] == 0) {
                        // Attendance counts by team.
                        $count_mystic = $cnt[$current_att_time]['count_mystic'] + $cnt[$current_att_time]['extra_mystic'];
                        $count_valor = $cnt[$current_att_time]['count_valor'] + $cnt[$current_att_time]['extra_valor'];
                        $count_instinct = $cnt[$current_att_time]['count_instinct'] + $cnt[$current_att_time]['extra_instinct'];
                        $count_remote = $cnt[$current_att_time]['count_remote'];
                        $count_late = $cnt[$current_att_time]['count_late'];

                        // Add to message.
                        $msg = raid_poll_message($msg, ' — ');
                        $msg = raid_poll_message($msg, (($count_mystic > 0) ? TEAM_B . $count_mystic . '  ' : ''));
                        $msg = raid_poll_message($msg, (($count_valor > 0) ? TEAM_R . $count_valor . '  ' : ''));
                        $msg = raid_poll_message($msg, (($count_instinct > 0) ? TEAM_Y . $count_instinct . '  ' : ''));
                        $msg = raid_poll_message($msg, (($cnt[$current_att_time]['count_no_team'] > 0) ? TEAM_UNKNOWN . $cnt[$current_att_time]['count_no_team'] . '  ' : ''));
                        $msg = raid_poll_message($msg, (($count_remote > 0) ? EMOJI_REMOTE . $count_remote . '  ' : ''));
                        $msg = raid_poll_message($msg, (($count_late > 0) ? EMOJI_LATE . $count_late . '  ' : ''));
                    }
                    $msg = raid_poll_message($msg, CR);
                }

                // Add section/header for pokemon
                if($previous_pokemon != $current_pokemon || $previous_att_time != $current_att_time) {
                    // Get counts for pokemons
                    $count_all = $cnt[$current_att_time]['count'];
                    $count_any_pokemon = $cnt[$current_att_time]['count_any_pokemon'];
                    $count_raid_pokemon = $cnt[$current_att_time]['count_raid_pokemon'];

                    // Show attendances when multiple pokemon are selected, unless all attending users voted for the raid boss + any pokemon
                    if($count_all != ($count_any_pokemon + $count_raid_pokemon)) {
                        // Add pokemon name.
                        $msg = raid_poll_message($msg, ($current_pokemon == 0) ? ('<b>' . getPublicTranslation('any_pokemon') . '</b>') : ('<b>' . get_local_pokemon_name($current_pokemon_id,$current_pokemon_form, true) . '</b>'));

                        // Attendance counts by team.
                        $current_att_time_poke = $cnt_pokemon[$current_att_time . '_' . $current_pokemon];
                        $count_att_time_poke_extrapeople = $current_att_time_poke['extra_mystic'] + $current_att_time_poke['extra_valor'] + $current_att_time_poke['extra_instinct'];
                        $poke_count_mystic = $current_att_time_poke['count_mystic'] + $current_att_time_poke['extra_mystic'];
                        $poke_count_valor = $current_att_time_poke['count_valor'] + $current_att_time_poke['extra_valor'];
                        $poke_count_instinct = $current_att_time_poke['count_instinct'] + $current_att_time_poke['extra_instinct'];
                        $poke_count_remote = $current_att_time_poke['count_remote'];
                        $poke_count_late = $current_att_time_poke['count_late'];

                        // Add to message.
                        $msg = raid_poll_message($msg, ' [' . ($current_att_time_poke['count'] + $count_att_time_poke_extrapeople) . '] — ');
                        $msg = raid_poll_message($msg, (($poke_count_mystic > 0) ? TEAM_B . $poke_count_mystic . '  ' : ''));
                        $msg = raid_poll_message($msg, (($poke_count_valor > 0) ? TEAM_R . $poke_count_valor . '  ' : ''));
                        $msg = raid_poll_message($msg, (($poke_count_instinct > 0) ? TEAM_Y . $poke_count_instinct . '  ' : ''));
                        $msg = raid_poll_message($msg, (($current_att_time_poke['count_no_team'] > 0) ? TEAM_UNKNOWN . ($current_att_time_poke['count_no_team']) : ''));
                        $msg = raid_poll_message($msg, (($poke_count_remote > 0) ? EMOJI_REMOTE . $poke_count_remote . '  ' : ''));
                        $msg = raid_poll_message($msg, (($poke_count_late > 0) ? EMOJI_LATE . $poke_count_late . '  ' : ''));
                        $msg = raid_poll_message($msg, CR);
                    }
                }

                // Add users: ARRIVED --- TEAM -- LEVEL -- NAME -- INVITE -- EXTRAPEOPLE
                $msg = raid_poll_message($msg, ($row['arrived']) ? (EMOJI_HERE . ' ') : (($row['late']) ? (EMOJI_LATE . ' ') : '└ '));
                //$msg = raid_poll_message($msg, ($row['arrived']) ? (($row['remote']) ? (EMOJI_REMOTE . ' ') : (EMOJI_HERE . ' ')) : (($row['late']) ? (EMOJI_LATE . ' ') : '└ '));
                $msg = raid_poll_message($msg, ($row['team'] === NULL) ? ($GLOBALS['teams']['unknown'] . ' ') : ($GLOBALS['teams'][$row['team']] . ' '));
                $msg = raid_poll_message($msg, ($row['level'] == 0) ? ('<b>00</b> ') : (($row['level'] < 10) ? ('<b>0' . $row['level'] . '</b> ') : ('<b>' . $row['level'] . '</b> ')));
                $msg = raid_poll_message($msg, '<a href="tg://user?id=' . $row['user_id'] . '">' . htmlspecialchars($row['name']) . '</a> ');
                $msg = raid_poll_message($msg, ($row['remote']) ? (EMOJI_REMOTE) : '');
                $msg = raid_poll_message($msg, ($raid_level == 'X' && $row['invite']) ? (EMOJI_INVITE . ' ') : '');
                $msg = raid_poll_message($msg, ($row['extra_mystic']) ? ('+' . $row['extra_mystic'] . TEAM_B . ' ') : '');
                $msg = raid_poll_message($msg, ($row['extra_valor']) ? ('+' . $row['extra_valor'] . TEAM_R . ' ') : '');
                $msg = raid_poll_message($msg, ($row['extra_instinct']) ? ('+' . $row['extra_instinct'] . TEAM_Y . ' ') : '');
                $msg = raid_poll_message($msg, CR);

                // Prepare next result
                $previous_att_time = $current_att_time;
                $previous_pokemon = $current_pokemon;
            }
        }

        // Get sums canceled/done for the raid
        $rs_cnt_cancel_done = my_query(
            "
            SELECT DISTINCT sum(DISTINCT raid_done = '1')  AS count_done,
                            sum(DISTINCT cancel = '1')     AS count_cancel,
                            sum(DISTINCT extra_mystic)     AS extra_mystic,
                            sum(DISTINCT extra_valor)      AS extra_valor,
                            sum(DISTINCT extra_instinct)   AS extra_instinct,
                            attend_time,
                            raid_done,
                            attendance.user_id
            FROM            attendance
              WHERE         raid_id = {$raid['id']}
                AND         (raid_done = 1
                            OR cancel = 1)
              GROUP BY      attendance.user_id, attend_time, raid_done
              ORDER BY      attendance.user_id, attend_time, raid_done
            "
        );

        // Init empty count array and count sum.
        $cnt_cancel_done = [];

        // Counter for cancel and done.
        $cnt_cancel = 0;
        $cnt_done = 0;

        while ($cnt_row_cancel_done = $rs_cnt_cancel_done->fetch()) {
            // Cancel count
            if($cnt_row_cancel_done['count_cancel'] > 0) {
                $cnt_cancel = $cnt_cancel + $cnt_row_cancel_done['count_cancel'] + $cnt_row_cancel_done['extra_mystic'] + $cnt_row_cancel_done['extra_valor'] + $cnt_row_cancel_done['extra_instinct'];
            }

            // Done count
            if($cnt_row_cancel_done['count_done'] > 0) {
                $cnt_done = $cnt_done + $cnt_cancel_done['count_done'] = $cnt_row_cancel_done['count_done'] + $cnt_row_cancel_done['extra_mystic'] + $cnt_row_cancel_done['extra_valor'] + $cnt_row_cancel_done['extra_instinct'];
            }
        }

        // Write to log.
        debug_log($cnt_cancel, 'Cancel count:');
        debug_log($cnt_done, 'Done count:');

        // Canceled or done?
        if(!$config->RAID_POLL_HIDE_DONE_CANCELED && ($cnt_cancel > 0 || $cnt_done > 0)) {
            // Get done and canceled attendances
            $rs_att = my_query(
                "
                SELECT      attendance.user_id,
                            attendance.attend_time,
                            attendance.cancel,
                            attendance.raid_done,
                            attendance.raid_id,
                            attendance.extra_valor,
                            attendance.extra_mystic,
                            attendance.extra_instinct,
                            users.name,
                            users.level,
                            users.team,
                            DATE_FORMAT(attend_time, '%Y%m%d%H%i%s') AS ts_att
                FROM        attendance
                LEFT JOIN   users
                ON          attendance.user_id = users.user_id
                  WHERE     raid_id = {$raid['id']}
                    AND     (raid_done = 1
                            OR cancel = 1)
                  GROUP BY  attendance.user_id, attendance.raid_done, attendance.attend_time, attendance.raid_done, attendance.cancel, attendance.raid_id, users.name, users.level, users.team, attendance.extra_valor, attendance.extra_mystic, attendance.extra_instinct
                  ORDER BY  raid_done,
                            users.team,
                            users.level desc,
                            users.name
                "
            );

            // Init cancel_done value.
            $cancel_done = 'CANCEL';

            // For each canceled / done.
            while ($row = $rs_att->fetch()) {
                // Attend time.
                $dt_att_time = dt2time($row['attend_time']);

                // Add section/header for canceled
                if($row['cancel'] == 1 && $cancel_done == 'CANCEL') {
                    $msg = raid_poll_message($msg, CR . TEAM_CANCEL . ' <b>' . getPublicTranslation('cancel') . ': </b>' . '[' . $cnt_cancel . ']' . CR);
                    $cancel_done = 'DONE';
                }

                // Add section/header for canceled
                if($row['raid_done'] == 1 && $cancel_done == 'CANCEL' || $row['raid_done'] == 1 && $cancel_done == 'DONE') {
                    $msg = raid_poll_message($msg, CR . TEAM_DONE . ' <b>' . getPublicTranslation('finished') . ': </b>' . '[' . $cnt_done . ']' . CR);
                    $cancel_done = 'END';
                }

                // Add users: TEAM -- LEVEL -- NAME -- CANCELED/DONE -- EXTRAPEOPLE
                $msg = raid_poll_message($msg, ($row['team'] === NULL) ? ('└ ' . $GLOBALS['teams']['unknown'] . ' ') : ('└ ' . $GLOBALS['teams'][$row['team']] . ' '));
                $msg = raid_poll_message($msg, ($row['level'] == 0) ? ('<b>00</b> ') : (($row['level'] < 10) ? ('<b>0' . $row['level'] . '</b> ') : ('<b>' . $row['level'] . '</b> ')));
                $msg = raid_poll_message($msg, '<a href="tg://user?id=' . $row['user_id'] . '">' . htmlspecialchars($row['name']) . '</a> ');
                $msg = raid_poll_message($msg, ($raid_level == 'X' && $row['invite']) ? (EMOJI_INVITE . ' ') : '');
                $msg = raid_poll_message($msg, ($row['cancel'] == 1) ? ('[' . (($row['ts_att'] == 0) ? (getPublicTranslation('anytime')) : ($dt_att_time)) . '] ') : '');
                $msg = raid_poll_message($msg, ($row['raid_done'] == 1) ? ('[' . (($row['ts_att'] == 0) ? (getPublicTranslation('anytime')) : ($dt_att_time)) . '] ') : '');
                $msg = raid_poll_message($msg, ($row['extra_mystic']) ? ('+' . $row['extra_mystic'] . TEAM_B . ' ') : '');
                $msg = raid_poll_message($msg, ($row['extra_valor']) ? ('+' . $row['extra_valor'] . TEAM_R . ' ') : '');
                $msg = raid_poll_message($msg, ($row['extra_instinct']) ? ('+' . $row['extra_instinct'] . TEAM_Y . ' ') : '');
                $msg = raid_poll_message($msg, CR);
            }
        }

        // Add no attendance found message.
        if ($cnt_all + $cnt_cancel + $cnt_done == 0) {
            $msg = raid_poll_message($msg, CR . getPublicTranslation('no_participants_yet') . CR);
        }
    }

    //Add custom message from the config.
    if (!empty($config->MAP_URL)) {
        $msg = raid_poll_message($msg, CR . $config->MAP_URL);
    }

    // Display creator.
    $msg = raid_poll_message($msg, ($raid['user_id'] && $raid['name']) ? (CR . getPublicTranslation('created_by') . ': <a href="tg://user?id=' . $raid['user_id'] . '">' . htmlspecialchars($raid['name']) . '</a>') : '');

    // Add update time and raid id to message.
    if(!$buttons_hidden) {
        $msg = raid_poll_message($msg, CR . '<i>' . getPublicTranslation('updated') . ': ' . dt2time('now', 'H:i:s') . '</i>');
    }
    $msg = raid_poll_message($msg, SP . SP . substr(strtoupper($config->BOT_ID), 0, 1) . '-ID = ' . $raid['id']); // DO NOT REMOVE! --> NEEDED FOR $config->CLEANUP PREPARATION!

/*
    $msg = raid_poll_message($msg, 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa');
    $msg = raid_poll_message($msg, 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa');
    $msg = raid_poll_message($msg, 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa');
    $msg = raid_poll_message($msg, 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa');
    $msg = raid_poll_message($msg, 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa');
    $msg = raid_poll_message($msg, 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa');
    $msg = raid_poll_message($msg, 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa');
*/

    // Return the message.
    return $msg;
}

?>
