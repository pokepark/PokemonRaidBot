<?php
// Write to log.
debug_log('SHARE_RAID_BY_LOCATION()');
require_once(LOGIC_PATH . '/get_raid_times.php');
require_once(LOGIC_PATH . '/show_raid_poll_small.php');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
$botUser->accessCheck('share-all');

if(isset($data['arg']) && $data['arg'] == 1) {
  $raid_id = $data['id'];

  // Get raid details.
  $raid = get_raid($raid_id);

  $keys = [];

  // Add keys to share.
  debug_log($raid, 'raw raid data for share: ');
  $keys_share = share_keys($raid['id'], 'raid_share', $update, $raid['level']);
  if(!empty($keys_share)) {
    $keys = array_merge($keys, $keys_share);
  } else {
    debug_log('There are no groups to share to, is SHARE_CHATS set?');
  }
  // Exit key
  $keys = universal_key($keys, '0', 'exit', '1', getTranslation('done'));

  // Get message.
  $msg = show_raid_poll_small($raid);

  // Build callback message string.
  $callback_response = 'OK';

  // Telegram JSON array.
  $tg_json = array();

  // Answer callback.
  $tg_json[] = answerCallbackQuery($update['callback_query']['id'], $callback_response, true);

  // Edit message.
  $tg_json[] = edit_message($update, $msg, $keys, false, true);

  // Telegram multicurl request.
  curl_json_multi_request($tg_json);

}else {
  if(!isset($update['message']['location'])) {
    send_message($update['message']['chat']['id'], '<b>' . getTranslation('invalid_input') . '</b>');
    exit();
  }
  $lat = (float)$update['message']['location']['latitude'];
  $lon = (float)$update['message']['location']['longitude'];
  $gps_diff = (float)0.01;

  // Build query.
  $rs = my_query('
    SELECT   raids.*,
           gyms.lat, gyms.lon, gyms.address, gyms.gym_name, gyms.ex_gym,
           users.name,
           TIME_FORMAT(TIMEDIFF(raids.end_time, UTC_TIMESTAMP()) + INTERVAL 1 MINUTE, \'%k:%i\') AS t_left
    FROM     raids
    LEFT JOIN  gyms
    ON     raids.gym_id = gyms.id
    LEFT JOIN  users
    ON     raids.user_id = users.user_id
    WHERE    raids.end_time>UTC_TIMESTAMP()
    AND    gyms.lat BETWEEN \''.($lat-$gps_diff).'\' AND \''.($lat+$gps_diff).'\'
    AND    gyms.lon BETWEEN \''.($lon-$gps_diff).'\' AND \''.($lon+$gps_diff).'\'
    ORDER BY   raids.end_time ASC LIMIT 20
  ');

  // Count results.
  $count = 0;

  // Init text and keys.
  $text = '';
  $keys = [];

  // Get raids.
  while ($raid = $rs->fetch()) {
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
    $time_left = $raid['t_left'];
    if ($now < $start) {
      $text .= get_raid_times($raid, true);
    // Raid has started already
    } else {
      // Add time left message.
      $text .= get_local_pokemon_name($raid['pokemon'], $raid['pokemon_form']) . ' — <b>' . getPublicTranslation('still') . SP . $time_left . 'h</b>' . CR . CR;
    }

    // Split pokemon and form to get the pokedex id.
    $pokedex_id = explode('-', $raid['pokemon'])[0];

    // Pokemon is an egg?
    $eggs = $GLOBALS['eggs'];
    if(in_array($pokedex_id, $eggs)) {
      $keys_text = EMOJI_EGG . SP . $gym_name;
    } else {
      $keys_text = $gym_name;
    }

    $keys[] = array(
      'text'          => $keys_text,
      'callback_data' => $raid['id'] . ':share_raid_by_location:1'
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
        'callback_data' => '0:exit:0'
      ]
    ];

    // Build message.
    $msg = '<b>' . getTranslation('list_all_active_raids') . ':</b>' . CR;
    $msg .= $text;
    $msg .= '<b>' . getTranslation('select_gym_name') . '</b>' . CR;
  }

  // Send message.
  send_message($update['message']['chat']['id'], $msg, $keys, ['reply_markup' => ['selective' => true, 'one_time_keyboard' => true]]);
}
