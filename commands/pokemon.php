<?php
// Write to log.
debug_log('POKEMON()');

// For debug.
//debug_log($update);
//debug_log($data);

if($botUser->accessCheck('pokemon-own', true)) {
  $userRestriction = 'AND raids.user_id = ?';
  $binds = [$update['message']['chat']['id']];
}elseif($botUser->accessCheck('pokemon-all', true)) {
  $userRestriction = '';
  $binds = [];
}else {
  $botUser->denyAccess();
}

$query = my_query('
  SELECT  raids.*, gyms.gym_name
  FROM    raids
  LEFT JOIN gyms
  ON      raids.gym_id = gyms.id
  WHERE   raids.end_time > UTC_TIMESTAMP
  ' . $userRestriction . '
  ORDER BY raids.end_time ASC
  LIMIT 20
', $binds);

if($query->rowCount() == 0) {
  $msg = '<b>' . getTranslation('no_active_raids_found') . '</b>';
  send_message($update['message']['chat']['id'], $msg);
  exit;
}

// Init text and keys.
$text = '';
$keys = [];

while ($row = $query->fetch()) {
  // Get times.
  $now = utcnow();
  $today = dt2date($now);
  $raid_day = dt2date($row['start_time']);
  $start = dt2time($row['start_time']);
  $end = dt2time($row['end_time']);

  // Split pokemon and form to get the pokedex id.
  $pokedex_id = explode('-', $row['pokemon'])[0];

  // Pokemon is an egg?
  $keys_text = $row['gym_name'];
  if(in_array($pokedex_id, $GLOBALS['eggs'])) {
    $keys_text = EMOJI_EGG . SP . $row['gym_name'];
  }

  // Set text and keys.
  $text .= $row['gym_name'] . CR;
  $text .= get_local_pokemon_name($row['pokemon'], $row['pokemon_form']) . SP . '—' . SP . (($raid_day == $today) ? '' : ($raid_day . ', ')) . $start . SP . getTranslation('to') . SP . $end . CR . CR;
  $keys[] = button($keys_text, ['raid_edit_poke', 'r' => $row['id'], 'rl' => $row['level']]);
}

// Get the inline key array.
$keys = inline_key_array($keys, 1);

// Add exit key.
$keys[][] = button(getTranslation('abort'), 'exit');

// Build message.
$msg = '<b>' . getTranslation('list_all_active_raids') . ':</b>' . CR;
$msg .= $text;
$msg .= '<b>' . getTranslation('select_gym_name') . '</b>' . CR;

// Send message.
send_message($update['message']['chat']['id'], $msg, $keys, ['reply_markup' => ['selective' => true, 'one_time_keyboard' => true]]);
