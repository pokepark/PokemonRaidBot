<?php
// Write to log.
debug_log('POKEMON()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
$botUser->accessCheck('access-bot');

// Count results.
$count = 0;

// Init text and keys.
$text = '';
$keys = [];

$query = my_query('
  SELECT
    raids.*, gyms.lat ,
    gyms.lon ,
    gyms.address ,
    gyms.gym_name ,
    gyms.ex_gym ,
    users. NAME
  FROM
    raids
  LEFT JOIN gyms ON raids.gym_id = gyms.id
  LEFT JOIN users ON raids.user_id = users.user_id
  WHERE
    raids.end_time > UTC_TIMESTAMP
  ORDER BY
    raids.end_time ASC
  LIMIT 20
');

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
  $keys[] = array(
    'text'          => $keys_text,
    'callback_data' => formatCallbackData(['raid_edit_poke', 'r' => $row['id'], 'rl' => $row['level']]),
  );

  // Counter++
  $count = $count + 1;
}

// Set message.
if($count == 0) {
  $msg = '<b>' . getTranslation('no_active_raids_found') . '</b>';
} else {
  // Get the inline key array.
  $keys = inline_key_array($keys, 1);

  // Add exit key.
  $keys[] = [
    [
      'text'          => getTranslation('abort'),
      'callback_data' => 'exit'
    ]
  ];

  // Build message.
  $msg = '<b>' . getTranslation('list_all_active_raids') . ':</b>' . CR;
  $msg .= $text;
  $msg .= '<b>' . getTranslation('select_gym_name') . '</b>' . CR;
}

// Build callback message string.
$callback_response = 'OK';

// Send message.
send_message($update['message']['chat']['id'], $msg, $keys, ['reply_markup' => ['selective' => true, 'one_time_keyboard' => true]]);
