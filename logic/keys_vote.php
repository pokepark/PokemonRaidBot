<?php
/**
 * Keys vote.
 * @param $raid
 * @return array
 */
function keys_vote($raid)
{
    global $config;
    // Init keys_time array.
    $keys_time = [];

    // Get current UTC time and raid UTC times.
    $now = utcnow();
    $end_time = $raid['end_time'];
    $start_time = $raid['start_time'];

    // Write to log.
    debug_log($now, 'UTC NOW:');
    debug_log($end_time, 'UTC END:');
    debug_log($start_time, 'UTC START:');

    // Raid ended already.
    if ($end_time < $now) {
        if($config->RAID_ENDED_HIDE_KEYS) {
            $keys = [];
        }else {
            $keys = [
                [
                    [
                        'text'          => getPublicTranslation('raid_done'),
                        'callback_data' => $raid['id'] . ':vote_refresh:1'
                    ]
                ]
            ];
        }
    // Raid is still running.
    } else {
        // Get current pokemon
        $raid_pokemon_id = $raid['pokemon'];
        $raid_pokemon_form_id = $raid['pokemon_form'];
        $raid_pokemon = $raid_pokemon_id . "-" . $raid_pokemon_form_id;

        // Get raid level
        $raid_level = $raid['level'];

        // Hide buttons for raid levels and pokemon
        $hide_buttons_raid_level = explode(',', $config->RAID_POLL_HIDE_BUTTONS_RAID_LEVEL);
        $hide_buttons_pokemon = explode(',', $config->RAID_POLL_HIDE_BUTTONS_POKEMON);

        // Show buttons to users?
        if(in_array($raid_level, $hide_buttons_raid_level) || in_array(($raid_pokemon_id . "-" . get_pokemon_form_name($raid_pokemon_id,$raid_pokemon_form_id)), $hide_buttons_pokemon) || in_array($raid_pokemon_id, $hide_buttons_pokemon)) {
            $keys = [];
        } else {
            // Extra Keys
            $buttons_alone = [
                'text'          => EMOJI_SINGLE,
                'callback_data' => $raid['id'] . ':vote_extra:0'
            ];
            $buttons_extra = [
                'text'          => '+ ' . EMOJI_IN_PERSON,
                'callback_data' => $raid['id'] . ':vote_extra:in_person'
            ];
            $buttons_extra_alien = [
                'text'          => '+ ' . EMOJI_ALIEN,
                'callback_data' => $raid['id'] . ':vote_extra:alien'
            ];

            // Can invite key
            $buttons_can_inv = [
                'text'          => EMOJI_CAN_INVITE,
                'callback_data' => $raid['id'] . ':vote_can_invite:0'
            ];

            // Remote Raid Pass key
            $buttons_remote = [
                'text'          => EMOJI_REMOTE,
                'callback_data' => $raid['id'] . ':vote_remote:0'
            ];

            // Want invite key
            $buttons_inv_plz = [
                'text'          => EMOJI_WANT_INVITE,
                'callback_data' => $raid['id'] . ':vote_want_invite:0'
            ];

            // Team and level keys.
            $buttons_teamlvl = [
                [
                    [
                        'text'          => 'Team',
                        'callback_data' => $raid['id'] . ':vote_team:0'
                    ],
                    [
                        'text'          => 'Lvl +',
                        'callback_data' => $raid['id'] . ':vote_level:up'
                    ],
                    [
                        'text'          => 'Lvl -',
                        'callback_data' => $raid['id'] . ':vote_level:down'
                    ]
                ]
            ];

            // Ex-Raid Invite key
            if ($raid['event'] == EVENT_ID_EX) {
                $buttons_ex_inv = [
                            'text'          => EMOJI_INVITE,
                            'callback_data' => $raid['id'] . ':vote_invite:0'
                ];
            }else {
                $buttons_ex_inv = [];
            }

            // Show icon, icon + text or just text.
            // Icon.
            if($config->RAID_VOTE_ICONS && !$config->RAID_VOTE_TEXT) {
                $text_here = EMOJI_HERE;
                $text_late = EMOJI_LATE;
                $text_done = TEAM_DONE;
                $text_cancel = TEAM_CANCEL;
            // Icon + text.
            } else if($config->RAID_VOTE_ICONS && $config->RAID_VOTE_TEXT) {
                $text_here = EMOJI_HERE . getPublicTranslation('here');
                $text_late = EMOJI_LATE . getPublicTranslation('late');
                $text_done = TEAM_DONE . getPublicTranslation('done');
                $text_cancel = TEAM_CANCEL . getPublicTranslation('cancellation');
            // Text.
            } else {
                $text_here = getPublicTranslation('here');
                $text_late = getPublicTranslation('late');
                $text_done = getPublicTranslation('done');
                $text_cancel = getPublicTranslation('cancellation');
            }

            // Status keys.
            $buttons_alarm = [
                'text'          => EMOJI_ALARM,
                'callback_data' => $raid['id'] . ':vote_status:alarm'
            ];
            $buttons_here = [
                'text'          => $text_here,
                'callback_data' => $raid['id'] . ':vote_status:arrived'
            ];
            $buttons_late = [
                'text'          => $text_late,
                'callback_data' => $raid['id'] . ':vote_status:late'
            ];
            $buttons_done = [
                'text'          => $text_done,
                'callback_data' => $raid['id'] . ':vote_status:raid_done'
            ];
            $buttons_cancel = [
                'text'          => $text_cancel,
                'callback_data' => $raid['id'] . ':vote_status:cancel'
            ];

            if(!$config->AUTO_REFRESH_POLLS) {
                $buttons_refresh = [
                            'text'          => EMOJI_REFRESH,
                            'callback_data' => $raid['id'] . ':vote_refresh:0'
                ];
            }else {
                $buttons_refresh = [];
            }

            if($raid['event_vote_key_mode'] == 1) {
                $keys_time = [
                                [
                                    'text'          => getPublicTranslation("Participate"),
                                    'callback_data' => $raid['id'] . ':vote_time:0'
                                ]
                            ];
            }else {
                if($raid['event_time_slots'] > 0) {
                    $RAID_SLOTS = $raid['event_time_slots'];
                }else {
                    $RAID_SLOTS = $config->RAID_SLOTS;
                }
                // Get current time.
                $now_helper = new DateTimeImmutable('now', new DateTimeZone('UTC'));
                $now_helper = $now_helper->format('Y-m-d H:i') . ':00';
                $dt_now = new DateTimeImmutable($now_helper, new DateTimeZone('UTC'));

                // Get direct start slot
                $direct_slot = new DateTimeImmutable($start_time, new DateTimeZone('UTC'));

                // Get first raidslot rounded up to the next 5 minutes
                // Get minute and convert modulo raidslot
                $five_slot = new DateTimeImmutable($start_time, new DateTimeZone('UTC'));
                $minute = $five_slot->format("i");
                $minute = $minute % 5;

                // Count minutes to next 5 multiple minutes if necessary
                if($minute != 0)
                {
                    // Count difference
                    $diff = 5 - $minute;
                    // Add difference
                    $five_slot = $five_slot->add(new DateInterval("PT".$diff."M"));
                }

                // Add $config->RAID_FIRST_START minutes to five minutes slot
                //$five_plus_slot = new DateTime($five_slot, new DateTimeZone('UTC'));
                $five_plus_slot = $five_slot;
                $five_plus_slot = $five_plus_slot->add(new DateInterval("PT".$config->RAID_FIRST_START."M"));

                // Get first regular raidslot
            // Get minute and convert modulo raidslot
                $first_slot = new DateTimeImmutable($start_time, new DateTimeZone('UTC'));
                $minute = $first_slot->format("i");
                $minute = $minute % $config->RAID_SLOTS;

                // Count minutes to next raidslot multiple minutes if necessary
                if($minute != 0)
                {
                    // Count difference
                    $diff = $config->RAID_SLOTS - $minute;
                    // Add difference
                    $first_slot = $first_slot->add(new DateInterval("PT".$diff."M"));
                }

                // Compare times slots to add them to keys.
                // Example Scenarios:
                // Raid 1: Start = 17:45, $config->RAID_FIRST_START = 10, $config->RAID_SLOTS = 15
                // Raid 2: Start = 17:36, $config->RAID_FIRST_START = 10, $config->RAID_SLOTS = 15
                // Raid 3: Start = 17:35, $config->RAID_FIRST_START = 10, $config->RAID_SLOTS = 15
                // Raid 4: Start = 17:31, $config->RAID_FIRST_START = 10, $config->RAID_SLOTS = 15
                // Raid 5: Start = 17:40, $config->RAID_FIRST_START = 10, $config->RAID_SLOTS = 15
                // Raid 6: Start = 17:32, $config->RAID_FIRST_START = 5, $config->RAID_SLOTS = 5

                // Write slots to log.
                debug_log($direct_slot, 'Direct start slot:');
                debug_log($five_slot, 'Next 5 Minute slot:');
                debug_log($first_slot, 'First regular slot:');

                // Add first slot only, as all slot times are identical
                if($direct_slot == $five_slot && $direct_slot == $first_slot) {
                    // Raid 1: 17:45 (17:45 == 17:45 && 17:45 == 17:45)

                    // Add first slot
                    if($first_slot >= $dt_now) {
                        $slot = $first_slot->format('Y-m-d H:i:s');
                        $keys_time[] = array(
                            'text'          => dt2time($slot),
                            'callback_data' => $raid['id'] . ':vote_time:' . utctime($slot, 'YmdHis')
                        );
                    }

                // Add either five and first slot or only first slot based on RAID_FIRST_START
                } else if($direct_slot == $five_slot && $five_slot < $first_slot) {
                    // Raid 3: 17:35 == 17:35 && 17:35 < 17:45
                    // Raid 5: 17:40 == 17:40 && 17:40 < 17:45

                    // Add next five minutes slot and first regular slot
                    if($five_plus_slot <= $first_slot) {
                        // Raid 3: 17:35, 17:45 (17:35 + 10min <= 17:45)

                        // Add five minutes slot
                        if($five_slot >= $dt_now) {
                            $slot = $five_slot->format('Y-m-d H:i:s');
                            $keys_time[] = array(
                                'text'          => dt2time($slot),
                                'callback_data' => $raid['id'] . ':vote_time:' . utctime($slot, 'YmdHis')
                            );
                        }

                        // Add first slot
                        if($first_slot >= $dt_now) {
                            $slot = $first_slot->format('Y-m-d H:i:s');
                            $keys_time[] = array(
                                'text'          => dt2time($slot),
                                'callback_data' => $raid['id'] . ':vote_time:' . utctime($slot, 'YmdHis')
                            );
                        }

                    // Add only first regular slot
                    } else {
                        // Raid 5: 17:45

                        // Add first slot
                        if($first_slot >= $dt_now) {
                            $slot = $first_slot->format('Y-m-d H:i:s');
                            $keys_time[] = array(
                                'text'          => dt2time($slot),
                                'callback_data' => $raid['id'] . ':vote_time:' . utctime($slot, 'YmdHis')
                            );
                        }
                    }

                // Add direct slot and first slot
                } else if($direct_slot < $five_slot && $five_slot == $first_slot) {
                    // Raid 6: 17:32 < 17:35 && 17:35 == 17:35
                    // Some kind of special case for a low value of RAID_SLOTS

                    // Add direct slot?
                    if($config->RAID_DIRECT_START) {
                        if($direct_slot >= $dt_now) {
                            $slot = $direct_slot->format('Y-m-d H:i:s');
                            $keys_time[] = array(
                                'text'          => dt2time($slot),
                                'callback_data' => $raid['id'] . ':vote_time:' . utctime($slot, 'YmdHis')
                            );
                        }
                    }

                    // Add first slot
                    if($first_slot >= $dt_now) {
                        $slot = $first_slot->format('Y-m-d H:i:s');
                        $keys_time[] = array(
                            'text'          => dt2time($slot),
                            'callback_data' => $raid['id'] . ':vote_time:' . utctime($slot, 'YmdHis')
                        );
                    }


                // Add either all 3 slots (direct slot, five minutes slot and first regular slot) or
                // 2 slots (direct slot and first slot) as $config->RAID_FIRST_START does not allow the five minutes slot to be added
                } else if($direct_slot < $five_slot && $five_slot < $first_slot) {
                    // Raid 2: 17:36 < 17:40 && 17:40 < 17:45
                    // Raid 4: 17:31 < 17:35 && 17:35 < 17:45

                    // Add all 3 slots
                    if($five_plus_slot <= $first_slot) {
                        // Raid 4: 17:31, 17:35, 17:45

                        // Add direct slot?
                        if($config->RAID_DIRECT_START) {
                            if($direct_slot >= $dt_now) {
                                $slot = $direct_slot->format('Y-m-d H:i:s');
                                $keys_time[] = array(
                                    'text'          => dt2time($slot),
                                    'callback_data' => $raid['id'] . ':vote_time:' . utctime($slot, 'YmdHis')
                                );
                            }
                        }

                        // Add five minutes slot
                        if($five_slot >= $dt_now) {
                            $slot = $five_slot->format('Y-m-d H:i:s');
                            $keys_time[] = array(
                                'text'          => dt2time($slot),
                                'callback_data' => $raid['id'] . ':vote_time:' . utctime($slot, 'YmdHis')
                            );
                        }

                        // Add first slot
                        if($first_slot >= $dt_now) {
                            $slot = $first_slot->format('Y-m-d H:i:s');
                            $keys_time[] = array(
                                'text'          => dt2time($slot),
                                'callback_data' => $raid['id'] . ':vote_time:' . utctime($slot, 'YmdHis')
                            );
                        }
                    // Add direct slot and first regular slot
                    } else {
                        // Raid 2: 17:36, 17:45

                        // Add direct slot?
                        if($config->RAID_DIRECT_START) {
                            if($direct_slot >= $dt_now) {
                                $slot = $direct_slot->format('Y-m-d H:i:s');
                                $keys_time[] = array(
                                    'text'          => dt2time($slot),
                                    'callback_data' => $raid['id'] . ':vote_time:' . utctime($slot, 'YmdHis')
                                );
                            }
                        }

                        // Add first slot
                        if($first_slot >= $dt_now) {
                            $slot = $first_slot->format('Y-m-d H:i:s');
                            $keys_time[] = array(
                                'text'          => dt2time($slot),
                                'callback_data' => $raid['id'] . ':vote_time:' . utctime($slot, 'YmdHis')
                            );
                        }
                    }

                // We missed all possible cases or forgot to include them in future else-if-clauses :D
                // Try to add at least the direct slot.
                } else {
                    // Add direct slot?
                    if($config->RAID_DIRECT_START) {
                        if($first_slot >= $dt_now) {
                            $slot = $direct_slot->format('Y-m-d H:i:s');
                            $keys_time[] = array(
                                'text'          => dt2time($slot),
                                'callback_data' => $raid['id'] . ':vote_time:' . utctime($slot, 'YmdHis')
                            );
                        }
                    }
                }


                // Init last slot time.
                $last_slot = new DateTimeImmutable($start_time, new DateTimeZone('UTC'));

                // Get regular slots
                // Start with second slot as first slot is already added to keys.
                $second_slot = $first_slot->add(new DateInterval("PT".$RAID_SLOTS."M"));
                $dt_end = new DateTimeImmutable($end_time, new DateTimeZone('UTC'));
                $regular_slots = new DatePeriod($second_slot, new DateInterval('PT'.$RAID_SLOTS.'M'), $dt_end);

                // Add regular slots.
                foreach($regular_slots as $slot){
                    $slot_end = $slot->add(new DateInterval('PT'.$config->RAID_LAST_START.'M'));
                    // Slot + $config->RAID_LAST_START before end_time?
                    if($slot_end < $dt_end) {
                        debug_log($slot, 'Regular slot:');
                        // Add regular slot.
                        if($slot >= $dt_now) {
                            $slot = $slot->format('Y-m-d H:i:s');
                            $keys_time[] = array(
                                'text'          => dt2time($slot),
                                'callback_data' => $raid['id'] . ':vote_time:' . utctime($slot, 'YmdHis')
                            );

                            // Set last slot for later.
                            $last_slot = new DateTimeImmutable($slot, new DateTimeZone('UTC'));
                        } else {
                            // Set last slot for later.
                            $slot = $slot->format('Y-m-d H:i:s');
                            $last_slot = new DateTimeImmutable($slot, new DateTimeZone('UTC'));
                        }
                    }
                }

                // Add raid last start slot
                // Set end_time to last extra slot, subtract $config->RAID_LAST_START minutes and round down to earlier 5 minutes.
                $last_extra_slot = $dt_end;
                $last_extra_slot = $last_extra_slot->sub(new DateInterval('PT'.$config->RAID_LAST_START.'M'));
                $s = 5 * 60;
                $last_extra_slot = $last_extra_slot->setTimestamp($s * floor($last_extra_slot->getTimestamp() / $s));
                //$time_to_last_slot = $last_extra_slot->diff($last_slot)->format("%a");

                // Last extra slot not conflicting with last slot and time to last regular slot larger than RAID_LAST_START?
                //if($last_extra_slot > $last_slot && $time_to_last_slot > $config->RAID_LAST_START)

                // Log last and last extra slot.
                debug_log($last_slot, 'Last slot:');
                debug_log($last_extra_slot, 'Last extra slot:');

                // Last extra slot not conflicting with last slot
                if($last_extra_slot > $last_slot) {
                    // Add last extra slot
                    if($last_extra_slot >= $dt_now) {
                        $slot = $last_extra_slot->format('Y-m-d H:i:s');
                        $keys_time[] = array(
                            'text'          => dt2time($slot),
                            'callback_data' => $raid['id'] . ':vote_time:' . utctime($slot, 'YmdHis')
                        );
                    }
                }

                // Attend raid at any time
                if($config->RAID_ANYTIME)
                {
                    $keys_time[] = array(
                        'text'          => getPublicTranslation('anytime'),
                        'callback_data' => $raid['id'] . ':vote_time:0'
                    );
                }
            }
            // Add time keys.
            $buttons_time = inline_key_array($keys_time, 4);

            // Hidden participants?
            if($config->RAID_POLL_HIDE_USERS_TIME > 0) {
                if($config->RAID_ANYTIME) {
                    $hide_users_sql = "AND (attend_time > (UTC_TIMESTAMP() - INTERVAL " . $config->RAID_POLL_HIDE_USERS_TIME . " MINUTE) OR attend_time = '".ANYTIME."')";
                } else {
                    $hide_users_sql = "AND attend_time > (UTC_TIMESTAMP() - INTERVAL " . $config->RAID_POLL_HIDE_USERS_TIME . " MINUTE)";
                }
            } else {
                $hide_users_sql = "";
            }

            // Get participants
            $rs = my_query(
                "
                SELECT    count(attend_time)                  AS count,
                          sum(pokemon = '0')                  AS count_any_pokemon,
                          sum(pokemon = '{$raid_pokemon}')    AS count_raid_pokemon
                FROM      attendance
                  WHERE   raid_id = {$raid['id']}
                          $hide_users_sql
                  AND     attend_time IS NOT NULL
                  AND     raid_done != 1
                  AND     cancel != 1
                 "
            );

            $row = $rs->fetch();

            // Count participants and participants by pokemon
            $count_pp = $row['count'];
            $count_any_pokemon = $row['count_any_pokemon'];
            $count_raid_pokemon = $row['count_raid_pokemon'];

            // Write to log.
            debug_log('Participants for raid with ID ' . $raid['id'] . ': ' . $count_pp);
            debug_log('Participants who voted for any pokemon: ' . $count_any_pokemon);
            debug_log('Participants who voted for ' . $raid_pokemon . ': ' . $count_raid_pokemon);

            // Zero Participants? Show only time buttons!
            if($count_pp == 0) {
                $keys = $buttons_time;
            } else {
                // Init keys pokemon array.
                $buttons_pokemon = [];

                // Show pokemon keys only if the raid boss is an egg
                if(in_array($raid_pokemon_id, $GLOBALS['eggs'])) {
                    // Get pokemon from database
                    $raid_spawn = dt2time($raid['spawn'], 'Y-m-d H:i'); // Convert utc spawntime to local time
                    $raid_bosses = get_raid_bosses($raid_spawn, $raid_level);

                    // Get eggs.
                    $eggs = $GLOBALS['eggs'];

                    if(count($raid_bosses) > 2) {
                        // Add key for each raid level
                        foreach($raid_bosses as $pokemon) {
                            if(in_array($pokemon['pokedex_id'], $eggs)) continue;
                            $buttons_pokemon[] = array(
                                'text'          => get_local_pokemon_name($pokemon['pokedex_id'], $pokemon['pokemon_form_id'], true),
                                'callback_data' => $raid['id'] . ':vote_pokemon:' . $pokemon['pokedex_id'] . '-' . $pokemon['pokemon_form_id']
                            );
                        }

                        // Add button if raid boss does not matter
                        $buttons_pokemon[] = array(
                            'text'          => getPublicTranslation('any_pokemon'),
                            'callback_data' => $raid['id'] . ':vote_pokemon:0'
                        );

                        // Finally add pokemon to keys
                        $buttons_pokemon = inline_key_array($buttons_pokemon, 2);
                    }
                }

                // Init keys array
                $keys = [];

                if($raid['event_poll_template'] != null) $template = json_decode($raid['event_poll_template']);
                else $template = $config->RAID_POLL_UI_TEMPLATE;
                $r=0;
                foreach($template as $row) {
                    foreach($row as $key) {
                        $v_name = 'buttons_'.$key;
                        if($key == 'teamlvl' or $key == 'pokemon' or $key == 'time') {
                            // Some button variables are "blocks" of keys, process them here
                            if(empty(${$v_name})) continue;
                            foreach(${$v_name} as $teamlvl) {
                                if(!isset($keys[$r])) $keys[$r] = [];
                                $keys[$r] = array_merge($keys[$r],$teamlvl);
                                $r++;
                            }
                            $r--;
                        }else {
                            if(empty(${$v_name})) continue;
                            $keys[$r][] = ${$v_name};
                        }
                    }
                    if(!empty($keys[$r][0])) $r++;
                }
            }
        }
    }

    // Return the keys.
    return $keys;
}

?>
