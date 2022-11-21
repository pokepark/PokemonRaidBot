<?php
require_once(LOGIC_PATH . '/get_pokemon_form_name.php');
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

  // Write to log.
  debug_log($now, 'UTC NOW:');
  debug_log($raid['end_time'], 'UTC END:');
  debug_log($raid['start_time'], 'UTC START:');

  // Raid ended already.
  if ($raid['raid_ended']) {
    if($config->RAID_ENDED_HIDE_KEYS) return [];
    return [
      [
        [
          'text'          => getPublicTranslation('raid_done'),
          'callback_data' => $raid['id'] . ':vote_refresh:1'
        ]
      ]
    ];
  }
  // Raid is still running.
  // Get current pokemon
  $raid_pokemon_id = $raid['pokemon'];
  $raid_pokemon_form_id = $raid['pokemon_form'];
  $raid_pokemon = $raid_pokemon_id . "-" . $raid_pokemon_form_id;

  // Get raid level
  $raid_level = $raid['level'];

  // Are remote players allowed for this raid?
  $raid_local_only = in_array($raid_level, RAID_LEVEL_LOCAL_ONLY);

  // Hide buttons for raid levels and pokemon
  $hide_buttons_raid_level = explode(',', $config->RAID_POLL_HIDE_BUTTONS_RAID_LEVEL);
  $hide_buttons_pokemon = explode(',', $config->RAID_POLL_HIDE_BUTTONS_POKEMON);

  // Show buttons to users?
  if(in_array($raid_level, $hide_buttons_raid_level) || in_array(($raid_pokemon_id . "-" . get_pokemon_form_name($raid_pokemon_id,$raid_pokemon_form_id)), $hide_buttons_pokemon) || in_array($raid_pokemon_id, $hide_buttons_pokemon)) {
    return [];
  }
  // Extra Keys
  $buttons['alone'] = [
    'text'          => EMOJI_SINGLE,
    'callback_data' => $raid['id'] . ':vote_extra:0'
  ];
  $buttons['extra'] = [
    'text'          => '+ ' . EMOJI_IN_PERSON,
    'callback_data' => $raid['id'] . ':vote_extra:in_person'
  ];

  // Show buttons regarding remote participation only if raid level allows it
  $buttons['extra_alien'] = $buttons['can_inv'] = $buttons['remote'] = $buttons['inv_plz'] = [];
  if(!$raid_local_only) {
    $buttons['extra_alien'] = [
      'text'          => '+ ' . EMOJI_ALIEN,
      'callback_data' => $raid['id'] . ':vote_extra:alien'
    ];

    // Can invite key
    $buttons['can_inv'] = [
      'text'          => EMOJI_CAN_INVITE,
      'callback_data' => $raid['id'] . ':vote_can_invite:0'
    ];

    // Remote Raid Pass key
    $buttons['remote'] = [
      'text'          => EMOJI_REMOTE,
      'callback_data' => $raid['id'] . ':vote_remote:0'
    ];

    // Want invite key
    $buttons['inv_plz'] = [
      'text'          => EMOJI_WANT_INVITE,
      'callback_data' => $raid['id'] . ':vote_want_invite:0'
    ];
  }

  // Team and level keys.
  $buttons['teamlvl'] = [
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
  $buttons['ex_inv'] = [];
  if ($raid['event'] == EVENT_ID_EX) {
    $buttons['ex_inv'] = [
      'text'          => EMOJI_INVITE,
      'callback_data' => $raid['id'] . ':vote_invite:0'
    ];
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
  $buttons['alarm'] = [
    'text'          => EMOJI_ALARM,
    'callback_data' => $raid['id'] . ':vote_status:alarm'
  ];
  $buttons['here'] = [
    'text'          => $text_here,
    'callback_data' => $raid['id'] . ':vote_status:arrived'
  ];
  $buttons['late'] = [
    'text'          => $text_late,
    'callback_data' => $raid['id'] . ':vote_status:late'
  ];
  $buttons['done'] = [
    'text'          => $text_done,
    'callback_data' => $raid['id'] . ':vote_status:raid_done'
  ];
  $buttons['cancel'] = [
    'text'          => $text_cancel,
    'callback_data' => $raid['id'] . ':vote_status:cancel'
  ];

  $buttons['refresh'] = [];
  if(!$config->AUTO_REFRESH_POLLS) {
    $buttons['refresh'] = [
      'text'          => EMOJI_REFRESH,
      'callback_data' => $raid['id'] . ':vote_refresh:0'
    ];
  }

  if($raid['event_vote_key_mode'] == 1) {
    $keys_time = [
      [
        'text'          => getPublicTranslation("Participate"),
        'callback_data' => $raid['id'] . ':vote_time:' . utctime($raid['start_time'], 'YmdHis')
      ]
    ];
  }else {
    $RAID_SLOTS = ($raid['event_time_slots'] > 0) ? $raid['event_time_slots'] : $config->RAID_SLOTS;
    $keys_time = generateTimeslotKeys($RAID_SLOTS, $raid);
  }
  // Add time keys.
  $buttons['time'] = inline_key_array($keys_time, 4);

  // Hidden participants?
  $hide_users_sql = '';
  if($config->RAID_POLL_HIDE_USERS_TIME > 0) {
    if($config->RAID_ANYTIME) {
      $hide_users_sql = 'AND (attend_time > (UTC_TIMESTAMP() - INTERVAL ' . $config->RAID_POLL_HIDE_USERS_TIME . ' MINUTE) OR attend_time = \''.ANYTIME.'\')';
    } else {
      $hide_users_sql = 'AND attend_time > (UTC_TIMESTAMP() - INTERVAL ' . $config->RAID_POLL_HIDE_USERS_TIME . ' MINUTE)';
    }
  }

  // Get participants
  $rs = my_query('
    SELECT  count(attend_time)          AS count,
          sum(pokemon = 0)          AS count_any_pokemon,
          sum(pokemon = ?)  AS count_raid_pokemon
    FROM    attendance
      WHERE   raid_id = ?
            ' . $hide_users_sql . '
      AND   attend_time IS NOT NULL
      AND   raid_done != 1
      AND   cancel != 1
    ', [$raid_pokemon, $raid['id']]
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
  if($row['count'] == 0) {
    return $buttons['time'];
  }

  // Init keys pokemon array.
  $buttons['pokemon'] = [];
  // Show pokemon keys only if the raid boss is an egg
  if(in_array($raid_pokemon_id, $GLOBALS['eggs'])) {
    // Get pokemon from database
    $raid_spawn = dt2time($raid['spawn'], 'Y-m-d H:i'); // Convert utc spawntime to local time
    $raid_bosses = get_raid_bosses($raid_spawn, $raid_level);

    if(count($raid_bosses) > 2) {
      // Add key for each raid level
      foreach($raid_bosses as $pokemon) {
        if(in_array($pokemon['pokedex_id'], $GLOBALS['eggs'])) continue;
        $buttons['pokemon'][] = array(
          'text'          => get_local_pokemon_name($pokemon['pokedex_id'], $pokemon['pokemon_form_id'], true),
          'callback_data' => $raid['id'] . ':vote_pokemon:' . $pokemon['pokedex_id'] . '-' . $pokemon['pokemon_form_id']
        );
      }

      // Add button if raid boss does not matter
      $buttons['pokemon'][] = array(
        'text'          => getPublicTranslation('any_pokemon'),
        'callback_data' => $raid['id'] . ':vote_pokemon:0'
      );

      // Finally add pokemon to keys
      $buttons['pokemon'] = inline_key_array($buttons['pokemon'], 2);
    }
  }

  // Init keys array
  $keys = [];

  $template = $config->RAID_POLL_UI_TEMPLATE;
  if($raid['event_poll_template'] != null) $template = json_decode($raid['event_poll_template']);
  $r = 0;
  foreach($template as $row) {
    foreach($row as $key) {
      if(!isset($buttons[$key]) or empty($buttons[$key])) continue;
      if($key == 'teamlvl' or $key == 'pokemon' or $key == 'time') {
        // Some button variables are "blocks" of keys, process them here
        foreach($buttons[$key] as $teamlvl) {
          if(!isset($keys[$r])) $keys[$r] = [];
          $keys[$r] = array_merge($keys[$r],$teamlvl);
          $r++;
        }
        $r--;
        continue;
      }
      $keys[$r][] = $buttons[$key];
    }
    if(!empty($keys[$r][0])) $r++;
  }

  // Return the keys.
  return $keys;
}

/**
 * Get active raid bosses at a certain time.
 * @param string $time - string, datetime, local time
 * @param int|string $raid_level - ENUM('1', '2', '3', '4', '5', '6', 'X')
 * @return array
 */
function get_raid_bosses($time, $raid_level)
{
  // Get raid level from database
  $rs = my_query('
      SELECT DISTINCT pokedex_id, pokemon_form_id
      FROM      raid_bosses
      WHERE       ? BETWEEN date_start AND date_end
      AND       raid_level = ?
    ', [$time, $raid_level]);
  debug_log('Checking active raid bosses for raid level '.$raid_level.' at '.$time.':');
  $raid_bosses = [];
  $egg_found = false;
  while ($result = $rs->fetch()) {
    $raid_bosses[] = $result;
    if($result['pokedex_id'] == '999'.$raid_level) $egg_found = true;
    debug_log('Pokedex id: '.$result['pokedex_id'].' | Form id: '.$result['pokemon_form_id']);
  }
  if(!$egg_found) $raid_bosses[] = ['pokedex_id' => '999'.$raid_level, 'pokemon_form_id' => 0]; // Add egg if it wasn't found from db
  return $raid_bosses;
}

/**
 * Get active raid bosses at a certain time.
 * @param int $RAID_SLOTS Length of the timeslot
 * @param array $raid
 * @return array
 */
function generateTimeslotKeys($RAID_SLOTS, $raid) {
  global $config;
  // Get current time.
  $now_helper = new DateTimeImmutable('now', new DateTimeZone('UTC'));
  $now_helper = $now_helper->format('Y-m-d H:i') . ':00';
  $dt_now = new DateTimeImmutable($now_helper, new DateTimeZone('UTC'));

  // Get direct start slot
  $direct_slot = new DateTimeImmutable($raid['start_time'], new DateTimeZone('UTC'));
  $directStartMinutes = $direct_slot->format('i');

  // Get first raidslot rounded up to the next 5 minutes
  $five_slot = new DateTimeImmutable($raid['start_time'], new DateTimeZone('UTC'));
  $minute = $directStartMinutes % 5;
  $diff = ($minute != 0) ? 5 - $minute : 5;
  $five_slot = $five_slot->add(new DateInterval('PT'.$diff.'M'));

  // Get first regular raidslot
  $first_slot = new DateTimeImmutable($raid['start_time'], new DateTimeZone('UTC'));
  $minute = $directStartMinutes % $RAID_SLOTS;

  // Count minutes to next raidslot multiple minutes if necessary
  if($minute != 0) {
    $diff = $RAID_SLOTS - $minute;
    $first_slot = $first_slot->add(new DateInterval('PT'.$diff.'M'));
  }

  // Write slots to log.
  debug_log($direct_slot, 'Direct start slot:');
  debug_log($five_slot, 'Next 5 Minute slot:');
  debug_log($first_slot, 'First regular slot:');
  $keys_time = [];
  // Add button for when raid starts time
  if($config->RAID_DIRECT_START && $direct_slot >= $dt_now) {
    $keys_time[] = array(
      'text'    => dt2time($direct_slot->format('Y-m-d H:i:s')),
      'callback_data' => $raid['id'] . ':vote_time:' . $direct_slot->format('YmdHis')
    );
  }
  // Add five minutes slot
  if($five_slot >= $dt_now && (empty($keys_time) || (!empty($keys_time) && $direct_slot != $five_slot))) {
    $keys_time[] = array(
      'text'    => dt2time($five_slot->format('Y-m-d H:i:s')),
      'callback_data' => $raid['id'] . ':vote_time:' . $five_slot->format('YmdHis')
    );
  }
  // Add the first normal slot
  if($first_slot >= $dt_now && $first_slot != $five_slot) {
    $keys_time[] = array(
      'text'    => dt2time($first_slot->format('Y-m-d H:i:s')),
      'callback_data' => $raid['id'] . ':vote_time:' . $first_slot->format('YmdHis')
    );
  }

  // Init last slot time.
  $last_slot = new DateTimeImmutable($raid['start_time'], new DateTimeZone('UTC'));

  // Get regular slots
  // Start with second slot as first slot is already added to keys.
  $dt_end = new DateTimeImmutable($raid['end_time'], new DateTimeZone('UTC'));
  $regular_slots = new DatePeriod($first_slot, new DateInterval('PT'.$RAID_SLOTS.'M'), $dt_end->sub(new DateInterval('PT'.$config->RAID_LAST_START.'M')), DatePeriod::EXCLUDE_START_DATE);

  // Add regular slots.
  foreach($regular_slots as $slot){
    debug_log($slot, 'Regular slot:');
    // Add regular slot.
    if($slot >= $dt_now) {
      $keys_time[] = array(
        'text'          => dt2time($slot->format('Y-m-d H:i:s')),
        'callback_data' => $raid['id'] . ':vote_time:' . $slot->format('YmdHis')
      );
    }
    // Set last slot for later.
    $last_slot = $slot;
  }

  // Add raid last start slot
  // Set end_time to last extra slot, subtract $config->RAID_LAST_START minutes and round down to earlier 5 minutes.
  $last_extra_slot = $dt_end;
  $last_extra_slot = $last_extra_slot->sub(new DateInterval('PT'.$config->RAID_LAST_START.'M'));
  $s = 5 * 60;
  $last_extra_slot = $last_extra_slot->setTimestamp($s * floor($last_extra_slot->getTimestamp() / $s));

  // Log last and last extra slot.
  debug_log($last_slot, 'Last slot:');
  debug_log($last_extra_slot, 'Last extra slot:');

  // Last extra slot not conflicting with last slot
  if($last_extra_slot > $last_slot && $last_extra_slot >= $dt_now) {
    // Add last extra slot
    $keys_time[] = array(
      'text'    => dt2time($last_extra_slot->format('Y-m-d H:i:s')),
      'callback_data' => $raid['id'] . ':vote_time:' . $last_extra_slot->format('YmdHis')
    );
  }

  // Attend raid at any time
  if($config->RAID_ANYTIME) {
    $keys_time[] = array(
      'text'    => getPublicTranslation('anytime'),
      'callback_data' => $raid['id'] . ':vote_time:0'
    );
  }
  return $keys_time;
}
