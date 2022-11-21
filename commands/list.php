<?php
// Write to log.
debug_log('LIST()');
require_once(LOGIC_PATH . '/resolve_raid_boss.php');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
$botUser->accessCheck($update, 'list');

$event_sql = 'event IS NULL';
if($botUser->accessCheck($update, 'ex-raids', true)) {
  if($botUser->accessCheck($update, 'event-raids', true))
    $event_sql = '';
  else
    $event_sql .= ' OR event = ' . EVENT_ID_EX;
}elseif($botUser->accessCheck($update, 'event-raids', true)) {
  $event_sql = 'event != ' . EVENT_ID_EX .' OR event IS NULL';
}
$event_sql = ($event_sql == '') ? '' : 'AND ('.$event_sql.')';
// Get last 12 active raids data.
$rs = my_query('
    SELECT     raids.pokemon, raids.pokemon_form, raids.id, raids.spawn, raids.start_time, raids.end_time, raids.level, raids.event,
               gyms.gym_name, gyms.ex_gym,
               events.name as event_name,
               (SELECT COUNT(*) FROM raids WHERE end_time>UTC_TIMESTAMP() ' . $event_sql  . ') as r_active
    FROM       raids
    LEFT JOIN  gyms
    ON         raids.gym_id = gyms.id
    LEFT JOIN  events
    ON         events.id = raids.event
    WHERE      end_time>UTC_TIMESTAMP()
    ' . $event_sql . '
    ORDER BY   end_time ASC
    LIMIT      12
');

// Get the raids.
$raids = $rs->fetchAll();

debug_log($raids);

// Did we get any raids?
if(count($raids) == 0) {
  $msg = '<b>' . getTranslation('no_active_raids_found') . '</b>';
  send_message($update['message']['chat']['id'], $msg, [], ['reply_markup' => ['selective' => true, 'one_time_keyboard' => true]]);
  exit();
}

debug_log($raids[0]['r_active'], 'Active raids:');
// More raids as we like?
if($raids[0]['r_active'] > 12 && $botUser->accessCheck($update, 'listall', true)) {
  // Forward to /listall
  debug_log('Too much raids, forwarding to /listall');
  $skip_access = true;
  include_once(ROOT_PATH . '/commands/listall.php');
  exit();
}

// Just enough raids to display at once
$text = '';
foreach($raids as $raid) {
  // Set text and keys.
  $gym_name = $raid['gym_name'];
  if(empty($gym_name)) {
    $gym_name = '';
  }
  $resolved_boss = resolve_raid_boss($raid['pokemon'], $raid['pokemon_form'], $raid['spawn'], $raid['level']);

  $text .= ($raid['ex_gym'] === 1 ? EMOJI_STAR . SP : '') . $gym_name . CR;
  $raid_day = dt2date($raid['start_time']);
  $now = utcnow();
  $today = dt2date($now);
  $start = dt2time($raid['start_time']);
  $end = dt2time($raid['end_time']);
  $text .= (!empty($raid['event_name']) ? $raid['event_name'] . CR : '' );
  $text .= get_local_pokemon_name($resolved_boss['pokedex_id'], $resolved_boss['pokemon_form_id']) . SP . '-' . SP . (($raid_day == $today) ? '' : ($raid_day . ', ')) . $start . SP . getTranslation('to') . SP . $end . CR . CR;

  // Pokemon is an egg?
  $keys_text = '';
  if(in_array($resolved_boss['pokedex_id'], $GLOBALS['eggs'])) {
    $keys_text = EMOJI_EGG . SP;
  }
  $keys_text .= ($raid['ex_gym'] === 1 ? EMOJI_STAR . SP : '') . $gym_name;

  $keys[] = array(
    'text'          => $keys_text,
    'callback_data' => $raid['id'] . ':raids_list:0'
  );
}

// Get the inline key array.
$keys = inline_key_array($keys, 1);

// Add exit key.
$keys[] = [
  [
    'text'          => getTranslation('abort'),
    'callback_data' => '0:exit:0'
  ]
];

// Build message.
$msg = '<b>' . getTranslation('list_all_active_raids') . ':</b>' . CR;
$msg .= $text;
$msg .= '<b>' . getTranslation('select_gym_name') . '</b>' . CR;

// Send message.
send_message($update['message']['chat']['id'], $msg, $keys, ['reply_markup' => ['selective' => true, 'one_time_keyboard' => true]]);
