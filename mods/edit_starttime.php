<?php
// Write to log.
debug_log('edit_starttime()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
$botUser->accessCheck('create');

// Get the argument.
$event_id = $data['e'] ?? NULL;
$raid_level = $data['rl'];
$arg = $data['o'] ?? '';

// Set the id.
$gym_id  = $data['g'];

// Are we creating an event?
if($event_id != NULL or $raid_level == 9) {
  // Init empty keys array.
  $keys = [];

  // Current time from the user
  // We let the user pick the raid date and time and convert to UTC afterwards in edit_date.php
  $tz = $config->TIMEZONE;
  $today = new DateTimeImmutable('now', new DateTimeZone($tz));

  // Create date buttons. Two days for Elite Raids, 15 days for EX raids.
  $days = ($raid_level == 9) ? 1 : 14;
  unset($data['o']);
  $buttonData = $data;
  $buttonData['callbackAction'] = 'edit_date';
  // Drop these from callback_data string, these are no longer needed
  unset($buttonData['fl']);
  unset($buttonData['ga']);
  for ($d = 0; $d <= $days; $d++) {
    // Add day to today.
    $today_plus_d = $today->add(new DateInterval("P".$d."D"));

    // Format date, e.g 14 April 2019
    $date_tz = $today_plus_d->format('Ymd');
    $text_day = $today_plus_d->format('d');
    $text_month = getTranslation('month_' . $today_plus_d->format('m'));
    $text_year = $today_plus_d->format('Y');

    // Add keys.
    $buttonData['t'] = $date_tz;
    $keys[] = array(
      'text'          => $text_day . SP . $text_month . SP . $text_year,
      'callback_data' => formatCallbackData($buttonData)
    );
  }

  // Get the inline key array.
  $keys = inline_key_array($keys, 2);

// Not creating an event
} else {
  if ($arg != "min" && $arg != "clock") {
    // Get default raid duration style from config
    if ($config->RAID_DURATION_CLOCK_STYLE) {
      $arg = "clock";
    } else {
      $arg = "min";
    }
  }

  // Init empty keys array.
  $keys = [];

  // Now
  $now = utcnow();

  // Copy received callbackData to new variable that we can edit
  $buttonData = $data;
  $buttonData['callbackAction'] = 'edit_time';
  if ($arg == "min") {
    // Set switch view.
    $switch_text = getTranslation('raid_starts_when_clocktime_view');
    $switch_view = "clock";
    $key_count = 5;

  } else {
    // Set switch view.
    $switch_text = getTranslation('raid_starts_when_minutes_view');
    $switch_view = "min";
    // Small screen fix
    $key_count = 4;
  }
  $now_plus_i = new DateTime($now, new DateTimeZone('UTC'));
  for ($i = 1; $i <= $config->RAID_EGG_DURATION; $i = $i + 1) {
    $now_plus_i->add(new DateInterval('PT1M'));
    $buttonData['t'] = $now_plus_i->format("H:i");
    if ($arg == 'min')
      $buttonText = floor($i / 60) . ':' . str_pad($i % 60, 2, '0', STR_PAD_LEFT);
    else
      $buttonText = dt2time($now_plus_i->format('Y-m-d H:i:s'));

    // Create the keys.
    $keys[] = array(
      'text'          => $buttonText,
      'callback_data' => formatCallbackData($buttonData)
    );
  }

  // Get the inline key array.
  $keys = inline_key_array($keys, $key_count);

  // Init empty keys other options array.
  $keys_opt = [];
  $keyData = $data;
  $keyData['callbackAction'] = 'edit_time';
  $keyData['o'] = 'm';
  $keyData['t'] = utctime($now,"H-i");
  // Raid already running
  $keys_opt[] = array(
    'text'	    => getTranslation('is_raid_active'),
    'callback_data' => formatCallbackData($keyData)
  );
  $keyData['callbackAction'] = 'edit_starttime';
  $keyData['o'] = $switch_view;
  unset($keyData['t']);
  // Switch view: clocktime / minutes until start
  $keys_opt[] = array(
    'text'	    => $switch_text,
    'callback_data' => formatCallbackData($keyData)
  );

  // Get the inline key array.
  $keys_opt = inline_key_array($keys_opt, 2);

  // Merge keys
  $keys = array_merge($keys, $keys_opt);

  // Write to log.
  debug_log($keys);

}

// No keys found.
if (!$keys) {
  // Create the keys.
  $keys = [
    [
      [
        'text'          => getTranslation('abort'),
        'callback_data' => '0:exit:0'
      ]
    ]
  ];
} else {
  $backData = $data;
  $backData['callbackAction'] = 'edit_pokemon';
  // Add navigation keys.
  $keys[] = [
    [
      'text' => getTranslation('back'),
      'callback_data' => formatCallbackData($backData)
    ],
    [
      'text' => getTranslation('abort'),
      'callback_data' => formatCallbackData(['callbackAction' => 'exit'])
    ]
  ];
}

// Build callback message string.
if ($arg != "min" && $arg != "clock") {
  $callback_response = getTranslation('pokemon_saved');
} else {
  $callback_response = getTranslation('raid_starts_when_view_changed');
}

// Telegram JSON array.
$tg_json = array();

// Answer callback.
$tg_json[] = answerCallbackQuery($update['callback_query']['id'], $callback_response, true);

// Set the message.
if ($arg == 'min') {
  $msg = getTranslation('raid_starts_when_minutes');
} else if ($event_id == EVENT_ID_EX) {
  $msg = getTranslation('raid_starts_when');
  $msg .= CR . CR . getTranslation('raid_select_date');
} else {
  $msg = getTranslation('raid_starts_when');
}

// Edit the message.
$tg_json[] = edit_message($update, $msg, $keys, false, true);

// Telegram multicurl request.
curl_json_multi_request($tg_json);
