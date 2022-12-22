<?php
// Write to log.
debug_log('edit_time()');
require_once(LOGIC_PATH . '/active_raid_duplication_check.php');
require_once(LOGIC_PATH . '/get_pokemon_by_table_id.php');
require_once(LOGIC_PATH . '/show_raid_poll_small.php');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
$botUser->accessCheck('create');

$raid_id = $data['r'] ?? 0;
$gym_id = $data['g'] ?? 0;
$event_id = $data['e'] ?? NULL;
$raid_level = $data['rl'] ?? 0;
$pokemon_table_id = $data['p'] ?? 0;
$starttime = $data['t'] ?? 0;
$opt_arg = $data['o'] ?? 'new-raid';

// Telegram JSON array.
$tg_json = array();

// Create raid under the following conditions::
// raid_id is 0, means we did not create it yet
// gym_id is not 0, means we have a gym_id for creation
if ($raid_id == 0 && $gym_id != 0) {
  // Replace "-" with ":" to get proper time format
  debug_log('Formatting the raid time properly now.');

  // Date was received in format YearMonthDayHourMinute so we need to reformat it to datetime
  $start_date_time = substr($starttime,0,4) . '-' . substr($starttime,4,2) . '-' . substr($starttime,6,2) . ' ' .  substr($starttime,8,2) . ':' .  substr($starttime,10,2) . ':00';
  // Event raids
  if($event_id != NULL) {
    $event_id = ($event_id == 'X') ? EVENT_ID_EX : $event_id;
    debug_log('Event time :D ... Setting raid date to ' . $start_date_time);
    $query = my_query('SELECT raid_duration FROM events WHERE id = ? LIMIT 1', [$event_id]);
    $result = $query->fetch();
    $duration = $result['raid_duration'] ?? $config->RAID_DURATION;
    $egg_duration = $config->RAID_EGG_DURATION;

  // Elite raids
  }elseif($raid_level == 9) {
    debug_log('Elite Raid time :D ... Setting raid date to ' . $start_date_time);
    $duration = $config->RAID_DURATION_ELITE;
    $egg_duration = $config->RAID_EGG_DURATION_ELITE;

  // Normal raids
  } else {
    debug_log('Received the following time for the raid: ' . $start_date_time);
    $duration = $config->RAID_DURATION;
    $egg_duration = $config->RAID_EGG_DURATION;
  }

  // Check for duplicate raid
  $duplicate_id = active_raid_duplication_check($gym_id);

  // Tell user the raid already exists and exit!
  // Unless we are creating an event raid
  if($duplicate_id != 0 &&
    !($event_id == EVENT_ID_EX && $botUser->accessCheck('ex-raids', true)) &&
    !($event_id != EVENT_ID_EX && $event_id != NULL && $botUser->accessCheck('event-raids', true))
  ) {
    $keys = [];
    $raid_id = $duplicate_id;
    $raid = get_raid($raid_id);
    $msg = EMOJI_WARN . SP . getTranslation('raid_already_exists') . SP . EMOJI_WARN . CR . show_raid_poll_small($raid);

    $keys = share_keys($raid_id, 'raid_share', $update, $raid['level']);

    // Add keys for sharing the raid.
    if(!empty($keys)) {
      // Exit key
      $keys[][] = button(getTranslation('abort'), 'exit');
    }

    // Answer callback.
    $tg_json[] = answerCallbackQuery($update['callback_query']['id'], getTranslation('raid_already_exists'), true);

    // Edit the message.
    $tg_json[] = edit_message($update, $msg, $keys, false, true);

    // Telegram multicurl request.
    curl_json_multi_request($tg_json);

    // Exit.
    exit();
  }
  // Continue with raid creation
  $pokemon_id_formid = get_pokemon_by_table_id($pokemon_table_id);

  // Saving event info to db. N = null
  debug_log("Event-id: ".$event_id);
  debug_log("Raid level: ".$raid_level);
  debug_log("Pokemon: ".$pokemon_id_formid['pokedex_id']."-".$pokemon_id_formid['pokemon_form_id']);

  // Create raid in database.
  $rs = my_query('
    INSERT INTO   raids
    SET   user_id = :userId,
          pokemon = :pokemon,
          pokemon_form = :pokemonForm,
          start_time = :startTime,
          spawn = DATE_SUB(start_time, INTERVAL ' . $egg_duration . ' MINUTE),
          end_time = DATE_ADD(start_time, INTERVAL ' . $duration . ' MINUTE),
          gym_id = :gymId,
          level = :level,
          event = :event
  ', [
    'userId' => $update['callback_query']['from']['id'],
    'pokemon' => $pokemon_id_formid['pokedex_id'],
    'pokemonForm' => $pokemon_id_formid['pokemon_form_id'],
    'startTime' => $start_date_time,
    'gymId' => $gym_id,
    'level' => $raid_level,
    'event' => $event_id,
  ]);

  // Get last insert id from db.
  $raid_id = $dbh->lastInsertId();

  // Write to log.
  debug_log('ID=' . $raid_id);
}

// Init empty keys array.
$keys = [];

// Raid pokemon duration short or 1 Minute / 5 minute time slots
if($opt_arg == 'm') {
  // 1-minute selection
  $slotsize = 1;

  $slotmax = $config->RAID_DURATION;

  for ($i = $slotmax; $i >= 15; $i = $i - $slotsize) {
    // Create the keys.
    $buttonText = floor($i / 60) . ':' . str_pad($i % 60, 2, '0', STR_PAD_LEFT);
    $keys[] = button($buttonText, ['edit_save', 'd' => $i, 'r' => $raid_id]);
  }

} else {
  debug_log('Comparing slot switch and argument for fast forward');
  $raidduration = $config->RAID_DURATION;

  // Write to log.
  debug_log('Doing a fast forward now!');
  debug_log('Changing data array first...');

  // Reset data array
  $data = [];
  $data['r'] = $raid_id;
  $data[0] = 'edit_save';
  $data['d'] = $raidduration;

  // Write to log.
  debug_log($data, '* NEW DATA= ');

  // Set module path by sent action name.
  $module = ROOT_PATH . '/mods/edit_save.php';

  // Write module to log.
  debug_log($module);

  // Check if the module file exists.
  if (file_exists($module)) {
    // Dynamically include module file and exit.
    include_once($module);
    exit();
  }
}

// Get the inline key array.
$keys = inline_key_array($keys, 5);

// Write to log.
debug_log($keys);

// Build callback message string.
if ($opt_arg != 'more' && $event_id == NULL) {
  $callback_response = 'OK';
} else {
  $callback_response = getTranslation('raid_starts_when_view_changed');
}

// Answer callback.
$tg_json[] = answerCallbackQuery($update['callback_query']['id'], $callback_response, true);

// Edit the message.
$tg_json[] = edit_message($update, getTranslation('how_long_raid'), $keys, false, true);

// Telegram multicurl request.
curl_json_multi_request($tg_json);
