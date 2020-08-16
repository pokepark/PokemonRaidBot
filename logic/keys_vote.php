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

    // Extra Keys
    $buttons_extra = [
        [
            [
                'text'          => EMOJI_SINGLE,
                'callback_data' => $raid['id'] . ':vote_extra:0'
            ],
            [
                'text'          => '+ ' . TEAM_B,
                'callback_data' => $raid['id'] . ':vote_extra:mystic'
            ],
            [
                'text'          => '+ ' . TEAM_R,
                'callback_data' => $raid['id'] . ':vote_extra:valor'
            ],
            [
                'text'          => '+ ' . TEAM_Y,
                'callback_data' => $raid['id'] . ':vote_extra:instinct'
            ]
        ]
    ];

    // Remote Raid Pass key
    $button_remote = [
        [
            [
                'text'          => EMOJI_REMOTE,
                'callback_data' => $raid['id'] . ':vote_remote:0'
            ]
        ]
    ];


    if($config->RAID_REMOTEPASS_USERS) {
        $buttons_extra[0] = array_merge($buttons_extra[0], $button_remote[0]);
    }

    // Team and level keys.
    if($config->RAID_POLL_HIDE_BUTTONS_TEAM_LVL) {
        $buttons_teamlvl = [];
    } else {
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
    }

    // Ex-Raid Invite key
    $button_invite = [
        [
            [
                'text'          => EMOJI_INVITE,
                'callback_data' => $raid['id'] . ':vote_invite:0'
            ]
        ]
    ];

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
    $buttons_status = [
        [
            [
                'text'          => EMOJI_REFRESH,
                'callback_data' => $raid['id'] . ':vote_refresh:0'
            ],
            [
              'text'          => EMOJI_ALARM,
              'callback_data' => $raid['id'] . ':vote_status:alarm'
            ],
            [
                'text'          => $text_here,
                'callback_data' => $raid['id'] . ':vote_status:arrived'
            ],
            [
                'text'          => $text_late,
                'callback_data' => $raid['id'] . ':vote_status:late'
            ],
            [
                'text'          => $text_done,
                'callback_data' => $raid['id'] . ':vote_status:raid_done'
            ],
            [
                'text'          => $text_cancel,
                'callback_data' => $raid['id'] . ':vote_status:cancel'
            ],
        ],
    ];

    // Raid ended already.
    if ($end_time < $now) {
        $keys = [
            [
                [
                    'text'          => getPublicTranslation('raid_done'),
                    'callback_data' => $raid['id'] . ':vote_refresh:1'
                ]
            ]
        ];
    // Raid is still running.
    } else {
        // Get current pokemon
        $raid_pokemon_id = $raid['pokemon'];
        $raid_pokemon_form_id = $raid['pokemon_form'];
        $raid_pokemon = $raid_pokemon_id . "-" . $raid_pokemon_form_id;
        
        // Get raid level
        $raid_level = '0';
        $raid_level = $raid['raid_level'];

        // Hide buttons for raid levels and pokemon
        $hide_buttons_raid_level = explode(',', $config->RAID_POLL_HIDE_BUTTONS_RAID_LEVEL);
        $hide_buttons_pokemon = explode(',', $config->RAID_POLL_HIDE_BUTTONS_POKEMON);

        // Show buttons to users?
        if(in_array($raid_level, $hide_buttons_raid_level) || in_array(($raid_pokemon_id . "-" . get_pokemon_form_name($raid_pokemon_id,$raid_pokemon_form_id)), $hide_buttons_pokemon) || in_array($raid_pokemon_id, $hide_buttons_pokemon)) {
            $keys = [];
        } else {
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
            $second_slot = $first_slot->add(new DateInterval("PT".$config->RAID_SLOTS."M"));
            $dt_end = new DateTimeImmutable($end_time, new DateTimeZone('UTC'));
            $regular_slots = new DatePeriod($second_slot, new DateInterval('PT'.$config->RAID_SLOTS.'M'), $dt_end);

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

            // Add time keys.
            $buttons_time = inline_key_array($keys_time, 4);

            // Hidden participants?
            if($config->RAID_POLL_HIDE_USERS_TIME > 0) {
                if($config->RAID_ANYTIME) {
                    $hide_users_sql = "AND (attend_time > (UTC_TIMESTAMP() - INTERVAL " . $config->RAID_POLL_HIDE_USERS_TIME . " MINUTE) OR attend_time = 0)";
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

                // Hide keys for specific cases
                $show_keys = true;
                // Make sure raid boss is not an egg
                if(!in_array($raid_pokemon_id, $GLOBALS['eggs'])) {
                    // Make sure we either have no participants
                    // OR all participants voted for "any" raid boss
                    // OR all participants voted for the hatched raid boss
                    // OR all participants voted for "any" or the hatched raid boss
                    if($count_pp == 0 || $count_pp == $count_any_pokemon || $count_pp == $count_raid_pokemon || $count_pp == ($count_any_pokemon + $count_raid_pokemon)) {
                        $show_keys = false;
                    }
                }

                // Add pokemon keys if we found the raid boss
                if ($raid_level != '0' && $show_keys) {
                    // Get pokemon from database
                    $rs = my_query(
                        "
                        SELECT    pokedex_id, pokemon_form_id
                        FROM      pokemon
                        WHERE     raid_level = '$raid_level'
                        "
                    );

                    // Init counter.
                    $count = 0;

                    // Get eggs.
                    $eggs = $GLOBALS['eggs'];

                    // Add key for each raid level
                    while ($pokemon = $rs->fetch()) {
                        if(in_array($pokemon['pokedex_id'], $eggs)) continue;
                        $buttons_pokemon[] = array(
                            'text'          => get_local_pokemon_name($pokemon['pokedex_id'], $pokemon['pokemon_form_id'], true),
                            'callback_data' => $raid['id'] . ':vote_pokemon:' . $pokemon['pokedex_id'] . '-' . $pokemon['pokemon_form_id']
                        );

                        // Counter
                        $count = $count + 1;
                    }

                    // Add pokemon keys if we have two or more pokemon
                    if($count >= 2) {
                        // Add button if raid boss does not matter
                        $buttons_pokemon[] = array(
                            'text'          => getPublicTranslation('any_pokemon'),
                            'callback_data' => $raid['id'] . ':vote_pokemon:0'
                        );

                        // Finally add pokemon to keys
                        $buttons_pokemon = inline_key_array($buttons_pokemon, 3);
                    } else {
                        // Reset pokemon buttons.
                        $buttons_pokemon = [];
                    }
                }

                // Init keys array
                $keys = [];

                // Get UI order from config and apply if nothing is missing!
                $keys_UI_config = explode(',', $config->RAID_POLL_UI_ORDER);
                $keys_default = explode(',', 'extra,teamlvl,time,pokemon,status');

                //debug_log($keys_UI_config);
                //debug_log($keys_default);

                // Add Ex-Raid Invite button for raid level X
                if ($raid_level == 'X') {
                    if($config->RAID_POLL_HIDE_BUTTONS_TEAM_LVL) {
                        $buttons_extra[0] = array_merge($buttons_extra[0], $button_invite[0]);
                    } else {
                        $buttons_teamlvl[0] = array_merge($buttons_teamlvl[0], $button_invite[0]);
                    }
                }

                // Compare if arrays have the same key/value pairs
                if(count($keys_UI_config) == count($keys_default) && count(array_diff($keys_UI_config, $keys_default)) == 0){
                    // Custom keys order
                    foreach ($keys_UI_config as $keyname) {
                        $keys = array_merge($keys, ${'buttons_' . $keyname});
                    }
                } else {
                    // Default keys order
                    $keys = array_merge($buttons_extra,$buttons_teamlvl,$buttons_time,$buttons_pokemon,$buttons_status);
                }
            }
        }
    }

    // Return the keys.
    return $keys;
}

?>
