<?php
// Write to log.
debug_log('HISTORY');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
$botUser->accessCheck('history');

$current_date = $data['d'];
$gym_first_letter = $data['fl'];
$gym_id = $data['g'];

// Get raids from database
$rs = my_query('
  SELECT  gyms.gym_name, raids.id, raids.start_time, raids.pokemon, raids.pokemon_form
  FROM    gyms
  LEFT JOIN raids
  ON    raids.gym_id = gyms.id
  LEFT JOIN attendance
  ON      attendance.raid_id = raids.id
  WHERE   gyms.id = ?
  AND     raids.end_time < UTC_TIMESTAMP()
  AND     attendance.id IS NOT NULL
  GROUP BY  raids.id, raids.start_time, raids.pokemon, raids.pokemon_form, gyms.gym_name
  ORDER BY  start_time
  ', [$gym_id]
);
while ($raid = $rs->fetch()) {
  $newData = $data;
  $newData[0] = 'history_raid';
  $newData['r'] = $raid['id'];
  $keys[][] = button(dt2time($raid['start_time']) . ': ' . get_local_pokemon_name($raid['pokemon'],$raid['pokemon_form']), $newData);
  $gym_name = $raid['gym_name'];
  $start_time = $raid['start_time'];
}
$nav_keys = [
  button(getTranslation('back'), ['history_gyms', 'd' => $current_date, 'fl' => $gym_first_letter]),
  button(getTranslation('abort'), 'exit')
];
$keys[] = $nav_keys;

$tg_json = [];

$tg_json[] = answerCallbackQuery($update['callback_query']['id'], 'OK', true);

$msg = getTranslation('history_title') . CR . CR;
$msg.= '<b>' . getTranslation('date') . ':</b> ' . getTranslation('month_' . substr($current_date,5,2)) . ' ' . substr($current_date,8) . CR;
$msg.= '<b>' . getTranslation('gym') . ':</b> ' . $gym_name . CR . CR;
$msg.= getTranslation('history_select_raid').':';

$tg_json[] = edit_message($update, $msg, $keys, false, true);

curl_json_multi_request($tg_json);

exit();
