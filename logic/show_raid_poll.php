<?php
/**
 * Show raid poll.
 * @param array $raid
 * @param bool $inline
 * @return array
 */
function show_raid_poll($raid, $inline = false)
{
    global $config;
    // Init empty message array.
    //$msg = '';
    $msg = array();

    // Get current pokemon
    $raid_pokemon_id = $raid['pokemon'];
    $raid_pokemon_form_id = $raid['pokemon_form'];
    $raid_pokemon_form_name = ($raid_pokemon_form_id != 0) ? get_pokemon_form_name($raid_pokemon_id, $raid_pokemon_form_id) : '';
    $raid_pokemon = $raid_pokemon_id . "-" . $raid_pokemon_form_id;

    // Get raid level
    $raid_level = $raid['level'];

    if($raid['event_name'] != NULL && $raid['event_name'] != "") {
        $msg = raid_poll_message($msg, "<b>".$raid['event_name']."</b>".CR, true);
    }

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
            $msg = raid_poll_message($msg, getPublicTranslation('gym') . ': ' . ($raid['ex_gym'] ? $ex_raid_gym_marker . SP : '') . '<b>' . $raid['gym_name'] . '</b>', true);
        }

        // Add team to message.
        if ($raid['gym_team']) {
            $msg = raid_poll_message($msg, SP . $GLOBALS['teams'][$raid['gym_team']], true);
        }

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
                WHERE   id = '{$raid['gym_id']}'
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
    $pokemon_weather = get_pokemon_weather($raid_pokemon_id, $raid_pokemon_form_id);
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
            $hide_users_sql = "AND (attend_time > (UTC_TIMESTAMP() - INTERVAL " . $config->RAID_POLL_HIDE_USERS_TIME . " MINUTE) OR attend_time = '". ANYTIME ."')";
        } else {
            $hide_users_sql = "AND attend_time > (UTC_TIMESTAMP() - INTERVAL " . $config->RAID_POLL_HIDE_USERS_TIME . " MINUTE)";
        }
    } else {
        $hide_users_sql = "";
    }

    // When the raid egg is hatched, hide all attendances that did not voted for hatched pokemon or any pokemon
    // Remaining attendances are combined and treated as users that voted for any pokemon from now on
    $order_by_sql = 'pokemon,';
    $combine_attendances = false;
    if(!in_array($raid['pokemon'], $GLOBALS['eggs'])) {
        $hide_users_sql.= 'AND (pokemon = \''.$raid['pokemon'].'-'.$raid['pokemon_form'].'\' OR pokemon = \'0\')';
        $order_by_sql = ''; // Remove sorting by pokemon since all attendances are combined
        $combine_attendances = true;
    }

    // Buttons for raid levels and pokemon hidden?
    $hide_buttons_raid_level = explode(',', $config->RAID_POLL_HIDE_BUTTONS_RAID_LEVEL);
    $hide_buttons_pokemon = explode(',', $config->RAID_POLL_HIDE_BUTTONS_POKEMON);
    $buttons_hidden = false;
    if(in_array($raid_level, $hide_buttons_raid_level) || in_array($raid_pokemon_id, $hide_buttons_pokemon) || in_array($raid_pokemon_id.'-'.$raid_pokemon_form_name, $hide_buttons_pokemon)) {
        $buttons_hidden = true;
    }

    // Get attendances
    $rs_attendance = my_query(
        "
        SELECT          attendance.*,
                        users.name,
                        users.nick,
                        users.trainername,
                        users.display_name,
                        users.trainercode,
                        users.level,
                        users.team
        FROM            attendance
        LEFT JOIN       users
          ON            attendance.user_id = users.user_id
          WHERE         raid_id = {$raid['id']}
                        {$hide_users_sql}
            AND         attend_time IS NOT NULL
          ORDER BY      attend_time,
                        {$order_by_sql}
                        can_invite DESC,
                        users.team,
                        arrived,
                        users.level desc,
                        users.name
        "
    );

    // Init empty attendance array and trigger variables
    $att_array = [];
    $cnt_array = [];
    $done_array = [];
    $cancel_array = [];
    $cnt_all = 0;
    $cnt_remote = 0;
    $cnt_want_invite = 0;
    $cnt_extra_alien = 0;
    $cnt_latewait = 0;
    $cnt_cancel = 0;
    $cnt_done = 0;
    $cnt_can_invite = 0;

    while ($attendance = $rs_attendance->fetch()) {
        // Attendance found
        $cnt_all = 1;
        $attendance_pokemon = $combine_attendances ? 0 : $attendance['pokemon']; // If raid egg has hatched, combine all attendances under 'any pokemon'

        // Check trainername
        $attendance = check_trainername($attendance);

        // Define variables if necessary
        if(!isset($cnt_array[$attendance['attend_time']][$attendance_pokemon]))
            $cnt_array[$attendance['attend_time']][$attendance_pokemon]['extra_alien']=$cnt_array[$attendance['attend_time']][$attendance_pokemon]['in_person']=$cnt_array[$attendance['attend_time']][$attendance_pokemon]['late']=$cnt_array[$attendance['attend_time']][$attendance_pokemon]['remote']=$cnt_array[$attendance['attend_time']][$attendance_pokemon]['want_invite']=$cnt_array[$attendance['attend_time']][$attendance_pokemon]['total']=0;
        if(!isset($cnt_array[$attendance['attend_time']]['other_pokemon'])) $cnt_array[$attendance['attend_time']]['other_pokemon'] = $cnt_array[$attendance['attend_time']]['raid_pokemon'] = $cnt_array[$attendance['attend_time']]['any_pokemon'] = 0;

        if($attendance['cancel'] == 0 && $attendance['raid_done'] == 0 && $attendance['can_invite'] == 0) {
            // These counts are used to control printing of pokemon/time headers, so just number of entries is enough
            if($attendance['pokemon'] != 0 && $raid_pokemon != $attendance['pokemon']){
                $cnt_array[$attendance['attend_time']]['other_pokemon']+=1;
            }elseif($attendance['pokemon'] != 0 && $raid_pokemon == $attendance['pokemon']) {
                $cnt_array[$attendance['attend_time']]['raid_pokemon']+= 1;
            }else {
                $cnt_array[$attendance['attend_time']]['any_pokemon']+= 1;
            }

            // Adding to total count of specific pokemon at specific time
            $cnt_array[$attendance['attend_time']][$attendance_pokemon]['total'] += 1 + $attendance['extra_in_person'] + $attendance['extra_alien'];

            if($attendance['want_invite'] == 0) {
                // Fill attendance array with results
                $att_array[$attendance['attend_time']][$attendance_pokemon][] = $attendance;

                // Fill counts array
                if($attendance['extra_alien'] > 0) {
                    $cnt_extra_alien = 1;
                    $cnt_array[$attendance['attend_time']][$attendance_pokemon]['extra_alien'] += $attendance['extra_alien'];
                }

                if($attendance['late'] == 1) {
                    $cnt_latewait = 1;
                    $cnt_array[$attendance['attend_time']][$attendance_pokemon]['late'] += 1 + $attendance['extra_in_person'] + $attendance['extra_alien'];
                }
                if($attendance['remote'] == 1) {
                    $cnt_remote = 1;
                    $cnt_array[$attendance['attend_time']][$attendance_pokemon]['remote'] += 1 + $attendance['extra_in_person'];
                }else {
                    $cnt_array[$attendance['attend_time']][$attendance_pokemon]['in_person'] += 1 + $attendance['extra_in_person'];
                }
            }else if($attendance['want_invite'] == 1) {
                // Create array key for attend time and pokemon to maintain correct sorting order
                if(!array_key_exists($attendance['attend_time'], $att_array)) {
                    $att_array[$attendance['attend_time']] = []; 
                    $att_array[$attendance['attend_time']][$attendance_pokemon] = [];
                }elseif(!array_key_exists($attendance_pokemon, $att_array[$attendance['attend_time']])) {
                    $att_array[$attendance['attend_time']][$attendance_pokemon] = [];
                }

                $cnt_array[$attendance['attend_time']][$attendance_pokemon]['want_invite'] += 1 + $attendance['extra_in_person'];
            }
        }else {
            if($attendance['raid_done']==1) {
                $cnt_done += 1 + $attendance['extra_in_person'] + $attendance['extra_alien'];
                $done_array[$attendance['user_id']] = $attendance; // Adding user_id as key to overwrite duplicate entries and thus only display user once even if they made multiple pokemon selections
            }else if($attendance['cancel']==1) {
                $cnt_cancel += 1 + $attendance['extra_in_person'] + $attendance['extra_alien'];
                $cancel_array[$attendance['user_id']] = $attendance;
            }else if($attendance['can_invite']==1) {
                $cnt_can_invite++;
                $att_array[$attendance['attend_time']][$attendance_pokemon][] = $attendance;
            }
        }
    }

    // Get attendances with invite beggars
    // Using a separate query so they can be displayed in the order they're in the db (who voted first)
    $rs_attendance_want_inv = my_query(
        "
        SELECT          attendance.*,
                        users.name,
                        users.nick,
                        users.trainername,
                        users.display_name,
                        users.trainercode,
                        users.level,
                        users.team
        FROM            attendance
        LEFT JOIN       users
          ON            attendance.user_id = users.user_id
          WHERE         raid_id = {$raid['id']}
          AND           want_invite = 1
          AND           cancel = 0
          AND           raid_done = 0
                        {$hide_users_sql}
            AND         attend_time IS NOT NULL
          ORDER BY      attend_time,
                        {$order_by_sql}
                        attendance.id
        "
    );
    while ($attendance = $rs_attendance_want_inv->fetch()) {
        // Attendance found
        $cnt_want_invite = 1;
        $attendance = check_trainername($attendance);

        // Fill attendance array with results
        if($combine_attendances) {
            $att_array[$attendance['attend_time']][0][] = $attendance;
        }else {
            $att_array[$attendance['attend_time']][$attendance['pokemon']][] = $attendance;
        }
    }
    // Raid has started and has participants
    if($time_now > $raid['start_time']) {
        // Add raid is done message.
        if($time_now > $raid['end_time']) {
            $msg = raid_poll_message($msg, '<b>' . getPublicTranslation('raid_done') . '</b>' . CR);
        // Add time left message.
        } else {
            $msg = raid_poll_message($msg, getPublicTranslation('raid') . ' — <b>' . getPublicTranslation('still') . ' ' . $time_left . 'h</b>' . CR);
        }
        if($cnt_all > 0 || $buttons_hidden) {
            // Display raid boss CP values.
            $pokemon_cp = get_formatted_pokemon_cp($raid_pokemon_id, $raid_pokemon_form_id, true);
            $msg = raid_poll_message($msg, (!empty($pokemon_cp)) ? ($pokemon_cp . CR) : '', true);
        }
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
        if($raid['event'] == EVENT_ID_EX) {
            $msg = raid_poll_message($msg, CR . EMOJI_WARN . ' <b>' . getPublicTranslation('exraid_pass') . '</b> ' . EMOJI_WARN . CR);
        }

        // Add event description
        if($raid['event_description'] != NULL && $raid['event_description'] != "") {
            $msg = raid_poll_message($msg, CR . "<b>".$raid['event_name']."</b>" . CR);
            $msg = raid_poll_message($msg, $raid['event_description'] . CR);
        }

        // Add event note
        if($raid['event_note'] != NULL && $raid['event_note'] != "") {
            $msg = raid_poll_message($msg, CR . $raid['event_note'] . CR);
        }
        // Add attendances message.
        if ($cnt_all > 0) {
            // Init previous attend time and pokemon
            $previous_att_time = 'FIRST_RUN';
            $previous_pokemon = 'FIRST_RUN';

            // Add hint for remote attendances.
            if($cnt_remote > 0 || $cnt_want_invite > 0 || $cnt_extra_alien > 0) {
                $remote_max_msg = str_replace('REMOTE_MAX_USERS', $config->RAID_REMOTEPASS_USERS_LIMIT, getPublicTranslation('remote_participants_max'));
                $msg = raid_poll_message($msg, CR . ($cnt_remote > 0 ? EMOJI_REMOTE : '') . ($cnt_extra_alien > 0 ? EMOJI_ALIEN : '') . ($cnt_want_invite > 0 ? EMOJI_WANT_INVITE : '') . SP . getPublicTranslation('remote_participants') . SP . '<i>' . $remote_max_msg . '</i>' . CR);
            }
            // Add hint for attendees that only invite.
            if($cnt_can_invite > 0) {
                $msg = raid_poll_message($msg, CR .  EMOJI_CAN_INVITE . SP . getPublicTranslation('alert_can_invite') . CR);
            }
            // Add start raid message
            if($cnt_all > 0 && $config->RAID_POLL_SHOW_START_LINK) {
                $msg = raid_poll_message($msg, CR . '<b>' . str_replace('START_CODE', '<a href="https://t.me/' . str_replace('@', '', $config->BOT_NAME) . '?start=c0de-' . $raid['id'] . '">' . getTranslation('telegram_bot_start') . '</a>', getPublicTranslation('start_raid')) . '</b>' . SP . '<i>' . getPublicTranslation('start_raid_info') . '</i>' . CR);
            }
            // Add hint for late attendances.
            if($config->RAID_LATE_MSG && $cnt_latewait > 0) {
                $late_wait_msg = "";
                if($config->RAID_LATE_TIME > 0) {
                    $late_wait_msg = str_replace('RAID_LATE_TIME', $config->RAID_LATE_TIME, getPublicTranslation('late_participants_wait'));
                }
                $msg = raid_poll_message($msg, CR . EMOJI_LATE . '<i>' . getPublicTranslation('late_participants') . ' ' . $late_wait_msg . '</i>' . CR);
            }
            // For each attendance.
            foreach($att_array as $att_time => $att_time_row) {
                // Set current attend time and pokemon
                $current_att_time = $att_time;
                $dt_att_time = dt2time($current_att_time);
                foreach($att_time_row as $att_pokemon => $att_pokemon_row) {
                    $current_pokemon = $att_pokemon;
                    $string_trainernames = '';
                    foreach($att_pokemon_row as $att_row) {
                        // Add section/header for time
                        if($previous_att_time != $current_att_time) {
                            // Add to message.
                            if($raid['event_vote_key_mode'] == 1) {
                                // When vote key mode is set to 1, only display "Participating" in title, since no other timeslot selections are available
                                $msg = raid_poll_message($msg, CR . '<b>' . getPublicTranslation('participating'). '</b>');
                            }else {
                                $msg = raid_poll_message($msg, CR . '<b>' . (($current_att_time == ANYTIME) ? (getPublicTranslation('anytime')) : ($dt_att_time)) . '</b>');
                            }

                            // Hide counts if other pokemon got selected. Show them in pokemon headers instead of attend time header
                            if ($cnt_array[$current_att_time][$current_pokemon]['total'] > 0 && $cnt_array[$current_att_time]['other_pokemon'] == 0) {
                                $msg = raid_poll_print_counts($msg, $cnt_array[$current_att_time][$current_pokemon]);
                            }else {
                                $msg = raid_poll_message($msg, CR );
                            }
                        }

                        // Add section/header for pokemon
                        if($previous_pokemon != $current_pokemon || $previous_att_time != $current_att_time) {
                            // Only display the pokemon titles if other pokemon than raid pokemon and any pokemon was selected
                            if($cnt_array[$current_att_time]['other_pokemon'] > 0 ) {
                                // Add pokemon name.
                                $pokemon_id_form = explode("-",$current_pokemon,2);
                                $msg = raid_poll_message($msg, ($current_pokemon == 0) ? ('<b>' . getPublicTranslation('any_pokemon') . '</b>') : ('<b>' . get_local_pokemon_name($pokemon_id_form[0],$pokemon_id_form[1], true) . '</b>'));

                                // Add counts to message.
                                $msg = raid_poll_print_counts($msg, $cnt_array[$current_att_time][$current_pokemon]);
                            }
                        }

                        if($config->RAID_POLL_ENABLE_HYPERLINKS_IN_NAMES) {
                            $trainername = '<a href="tg://user?id=' . $att_row['user_id'] . '">' . htmlspecialchars($att_row['name']) . '</a> ';
                        }else {
                            $trainername = htmlspecialchars($att_row['name']) . ' ';
                        }
                        if(isset($att_row['trainername']) && $config->RAID_POLL_SHOW_TRAINERNAME_STRING && $att_row['want_invite']) {
                            if(!empty($string_trainernames)) $string_trainernames .= ',';
                            $string_trainernames .= $att_row['trainername'];
                        }
                        // Add users: ARRIVED --- TEAM -- LEVEL -- NAME -- INVITE -- EXTRAPEOPLE
                        $msg = raid_poll_message($msg, ($att_row['arrived']) ? (EMOJI_HERE . ' ') : (($att_row['late']) ? (EMOJI_LATE . ' ') : '└ '));
                        $msg = raid_poll_message($msg, ($att_row['team'] === NULL) ? ($GLOBALS['teams']['unknown'] . ' ') : ($GLOBALS['teams'][$att_row['team']] . ' '));
                        $msg = raid_poll_message($msg, ($att_row['level'] == 0) ? ('<b>00</b> ') : (($att_row['level'] < 10) ? ('<b>0' . $att_row['level'] . '</b> ') : ('<b>' . $att_row['level'] . '</b> ')));
                        $msg = raid_poll_message($msg, $trainername);
                        $msg = raid_poll_message($msg, ($att_row['remote']) ? (EMOJI_REMOTE) : '');
                        $msg = raid_poll_message($msg, ($raid['event'] == EVENT_ID_EX && $att_row['invite']) ? (EMOJI_INVITE . ' ') : '');
                        $msg = raid_poll_message($msg, ($att_row['extra_in_person']) ? ('+' . $att_row['extra_in_person'] . EMOJI_IN_PERSON . ' ') : '');
                        $msg = raid_poll_message($msg, ($att_row['extra_alien']) ? ('+' . $att_row['extra_alien'] . EMOJI_ALIEN . ' ') : '');
                        $msg = raid_poll_message($msg, ($att_row['want_invite']) ? (EMOJI_WANT_INVITE) : '');
                        $msg = raid_poll_message($msg, ($att_row['can_invite']) ? (EMOJI_CAN_INVITE) : '');
                        $msg = raid_poll_message($msg, ($config->RAID_POLL_SHOW_TRAINERCODE && ($att_row['want_invite'] || $att_row['can_invite']) && !is_null($att_row['trainercode'])) ? ' <code>' . $att_row['trainercode'] . ' </code>': '');

                        $msg = raid_poll_message($msg, CR);

                        // Prepare next result
                        $previous_att_time = $current_att_time;
                        $previous_pokemon = $current_pokemon;
                    }
                    if(!empty($string_trainernames)) {
                        $msg = raid_poll_message($msg, '<code>' . $string_trainernames . '</code>' . CR);
                    }
                }
            }
        }

        // Canceled or done?
        if(!$config->RAID_POLL_HIDE_DONE_CANCELED && ($cnt_cancel > 0 || $cnt_done > 0)) {
            // Init cancel_done value.
            $cancel_done = 'CANCEL';
            $array_cancel_done = array_merge($cancel_array,$done_array);
            // For each canceled / done.
            foreach ($array_cancel_done as $row) {
                // Attend time.
                $dt_att_time = dt2time($row['attend_time']);

                // Add section/header for canceled
                if($row['cancel'] == 1 && $cancel_done == 'CANCEL') {
                    $msg = raid_poll_message($msg, CR . TEAM_CANCEL . ' <b>' . getPublicTranslation('cancelled') . ': </b>' . '[' . $cnt_cancel . ']' . CR);
                    $cancel_done = 'DONE';
                }

                // Add section/header for canceled
                if($row['raid_done'] == 1 && $cancel_done == 'CANCEL' || $row['raid_done'] == 1 && $cancel_done == 'DONE') {
                    $msg = raid_poll_message($msg, CR . TEAM_DONE . ' <b>' . getPublicTranslation('finished') . ': </b>' . '[' . $cnt_done . ']' . CR);
                    $cancel_done = 'END';
                }
                if($config->RAID_POLL_ENABLE_HYPERLINKS_IN_NAMES) {
                    $trainername = '<a href="tg://user?id=' . $row['user_id'] . '">' . htmlspecialchars($row['name']) . '</a> ';
                }else {
                    $trainername = htmlspecialchars($row['name']) . ' ';
                }

                // Add users: TEAM -- LEVEL -- NAME -- CANCELED/DONE -- EXTRAPEOPLE
                $msg = raid_poll_message($msg, ($row['team'] === NULL) ? ('└ ' . $GLOBALS['teams']['unknown'] . ' ') : ('└ ' . $GLOBALS['teams'][$row['team']] . ' '));
                $msg = raid_poll_message($msg, ($row['level'] == 0) ? ('<b>00</b> ') : (($row['level'] < 10) ? ('<b>0' . $row['level'] . '</b> ') : ('<b>' . $row['level'] . '</b> ')));
                $msg = raid_poll_message($msg, $trainername);
                $msg = raid_poll_message($msg, ($raid['event'] == EVENT_ID_EX && $row['invite']) ? (EMOJI_INVITE . ' ') : '');
                if($raid['event_vote_key_mode'] != 1) {
                    $msg = raid_poll_message($msg, ($row['cancel'] == 1 || $row['raid_done'] == 1) ? ('[' . (($row['attend_time'] == ANYTIME) ? (getPublicTranslation('anytime')) : ($dt_att_time)) . '] ') : '');
                }
                $msg = raid_poll_message($msg, ($row['extra_in_person']) ? ('+' . $row['extra_in_person'] . EMOJI_IN_PERSON . ' ') : '');
                $msg = raid_poll_message($msg, ($row['extra_alien']) ? ('+' . $row['extra_alien'] . EMOJI_ALIEN . ' ') : '');
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
    if($config->RAID_POLL_SHOW_CREATOR) {
        $msg = raid_poll_message($msg, ($raid['user_id'] && $raid['name']) ? (CR . getPublicTranslation('created_by') . ': <a href="tg://user?id=' . $raid['user_id'] . '">' . htmlspecialchars($raid['name']) . '</a>') : '');
    }

    // Add update time and raid id to message.
    $msg = raid_poll_message($msg, CR . '<i>' . getPublicTranslation('updated') . ': ' . dt2time('now', 'H:i:s') . '</i>');
    if($inline && ($buttons_hidden or ($raid['end_time'] < $time_now && $config->RAID_ENDED_HIDE_KEYS))) {
        // Only case this is needed anymore is a poll shared via inline that has no vote keys
        $msg = raid_poll_message($msg, SP . SP . substr(strtoupper($config->BOT_ID), 0, 1) . '-ID = ' . $raid['id']);
    }
    // Return the message.
    return $msg;
}

/**
 * Print counts to raid poll headers.
 * @param $msg - The array containing short and long poll messages
 * @param $cnt_array - A row of cnt_array created in show_raid_poll for specific time/pokemon
 * @return array
 */
function raid_poll_print_counts($msg, $cnt_array) {
    // Attendance counts
    $count_in_person = $cnt_array['in_person'];
    $count_remote = $cnt_array['remote'];
    $count_extra_alien = $cnt_array['extra_alien'];
    $count_want_invite = $cnt_array['want_invite'];
    $count_late = $cnt_array['late'];
    $count_total = (is_numeric($cnt_array['total'])?$cnt_array['total']:0);

    // Add to message.
    $msg = raid_poll_message($msg, ' [' . ($count_total) . '] — ');
    $msg = raid_poll_message($msg, (($count_in_person > 0) ? EMOJI_IN_PERSON . $count_in_person . '  ' : ''));
    $msg = raid_poll_message($msg, (($count_remote > 0) ? EMOJI_REMOTE . $count_remote . '  ' : ''));
    $msg = raid_poll_message($msg, (($count_extra_alien > 0) ? EMOJI_ALIEN . $count_extra_alien . '  ' : ''));
    $msg = raid_poll_message($msg, (($count_want_invite > 0) ? EMOJI_WANT_INVITE . $count_want_invite . '  ' : ''));
    $msg = raid_poll_message($msg, (($count_late > 0) ? EMOJI_LATE . $count_late . '  ' : ''));
    $msg = raid_poll_message($msg, CR);
    return $msg;
}
?>
