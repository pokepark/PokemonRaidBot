<?php
// Write to log.
debug_log('code_start');

// For debug.
//debug_log($update);
//debug_log($data);

// Allow anyone to use /code
// Check access.
//$botUser->accessCheck('list');

// Get raid
$raid = get_raid($code_raid_id);

// Init text and keys.
$text = '';
$keys = [];

// Get current UTC time and raid end UTC time.
$now = utcnow();
$end_time = $raid['end_time'];

// Raid ended already.
if ($end_time > $now) {
  // Set text and keys.
  $gym_name = $raid['gym_name'];
  if(empty($gym_name)) {
    $gym_name = '';
  }

  $text .= $gym_name . CR;
  $raid_day = dt2date($raid['start_time']);
  $now = utcnow();
  $today = dt2date($now);
  $start = dt2time($raid['start_time']);
  $end = dt2time($raid['end_time']);
  $text .= get_local_pokemon_name($raid['pokemon'], $raid['pokemon_form']) . SP . '-' . SP . (($raid_day == $today) ? '' : ($raid_day . ', ')) . $start . SP . getTranslation('to') . SP . $end . CR . CR;

  // Add exit key.
  $keys[0][] = button(getTranslation('start_raid_public'), ['code', 'r' => $raid['id'], 'a' => 'public-unconfirmed']);
  $keys[0][] = button(getTranslation('start_raid_private'), ['code', 'r' => $raid['id'], 'a' => '0-0-0-add']);
  $keys[1][] = button(getTranslation('abort'), 'exit');

  // Build message.
  $msg = '<b>' . getTranslation('start_raid_now') . ':</b>' . CR . CR;
  $msg .= $text;
} else {
  $msg = '<b>' . getTranslation('group_code_share') . ':</b>' . CR;
  $msg .= '<b>' . getTranslation('no_active_raids_found') . '</b>';
}

// Send message.
send_message(create_chat_object([$update['message']['chat']['id']]), $msg, $keys, ['reply_markup' => ['selective' => true, 'one_time_keyboard' => true]]);
