<?php
// Write to log.
debug_log('DELETE()');

// For debug.
//debug_log($update);
//debug_log($data);

if($botUser->accessCheck('delete-own', true)) {
  $userRestriction = 'AND raids.user_id = ?';
  $binds = [$update['message']['chat']['id']];
}elseif($botUser->accessCheck('delete-all', true)) {
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
  send_message(create_chat_object([$update['message']['chat']['id']]), $msg);
  exit;
}

// Init text and keys.
$text = '';
$keys = [];

while ($row = $query->fetch()) {
  // Set text and keys.
  $text .= $row['gym_name'] . CR;
  $now = utcnow();
  $today = dt2date($now);
  $raid_day = dt2date($row['start_time']);
  $start = dt2time($row['start_time']);
  $end = dt2time($row['end_time']);
  $text .= get_local_pokemon_name($row['pokemon'], $row['pokemon_form']) . SP . '—' . SP . (($raid_day == $today) ? '' : ($raid_day . ', ')) . $start . SP . getTranslation('to') . SP . $end . CR . CR;
  $keys[] = button($row['gym_name'], ['raids_delete', 'r' => $row['id']]);

}

// Get the inline key array.
$keys = inline_key_array($keys, 1);

// Add exit key.
$keys[][] = button(getTranslation('abort'), 'exit');


// Build message.
$msg = '<b>' . getTranslation('list_all_active_raids') . ':</b>' . CR;
$msg .= $text;
$msg .= '<b>' . getTranslation('select_gym_name') . '</b>' . CR;

// Build callback message string.
$callback_response = 'OK';

// Send message.
send_message(create_chat_object([$update['message']['chat']['id']]), $msg, $keys, ['reply_markup' => ['selective' => true, 'one_time_keyboard' => true]]);
