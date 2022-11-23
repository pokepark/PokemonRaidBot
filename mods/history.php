<?php
// Write to log.
debug_log('HISTORY');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
$botUser->accessCheck('history');

// Expected callback data: [Day number (0-31), DD]:history:[Year and month, YYYY-MM]

require_once(LOGIC_PATH .'/history.php');

$current_day = $data['id'];
$current_year_month = $data['arg'];

if($current_day == 0) {
  $msg_keys = create_history_date_msg_keys($current_year_month);
  $msg = $msg_keys[0];
  $keys = $msg_keys[1];
}else {
  $msg = getTranslation('history_title') . CR . CR;
  $msg.= '<b>' . getTranslation('date') . ':</b> ' . getTranslation('month_' . substr($current_year_month,5)) . ' ' . $current_day . CR . CR;
  $msg.= getTranslation('select_gym_first_letter');
  // Special/Custom gym letters?
  if(!empty($config->RAID_CUSTOM_GYM_LETTERS)) {
    // Explode special letters.
    $special_keys = explode(',', $config->RAID_CUSTOM_GYM_LETTERS);
    $select_query = 'CASE';
    foreach($special_keys as $id => $letter)
    {
      $letter = trim($letter);
      debug_log($letter, 'Special gym letter:');
      // Fix chinese chars, prior: $length = strlen($letter);
      $length = strlen(utf8_decode($letter));
      $select_query .= SP . "WHEN UPPER(LEFT(gym_name, " . $length . ")) = '" . $letter . "' THEN UPPER(LEFT(gym_name, " . $length . "))" . SP;
    }
    $select_query .= 'ELSE UPPER(LEFT(gym_name, 1)) END';
  }else {
    $select_query = 'DISTINCT UPPER(SUBSTR(gym_name, 1, 1))';
  }
  $date = $current_year_month.'-'.$current_day;

  $rs = my_query('
      SELECT '.$select_query.' AS first_letter
      FROM    raids
      LEFT JOIN gyms
      ON    raids.gym_id = gyms.id
      LEFT JOIN attendance
      ON    attendance.raid_id = raids.id
      WHERE   date_format(start_time, "%Y-%m-%d") =  \''.$date.'\'
      AND     raids.end_time < UTC_TIMESTAMP()
      AND     attendance.id IS NOT NULL
      AND     gyms.gym_name IS NOT NULL
      ORDER BY  first_letter
  ');

  // Init empty keys array.
  $keys = [];

  while ($gym = $rs->fetch()) {
	// Add first letter to keys array
    $keys[] = array(
      'text'          => $gym['first_letter'],
      'callback_data' => $date . ':history_gyms:' . $gym['first_letter']
    );
  }
  // Format buttons
  $keys = inline_key_array($keys, 4);

  $nav_keys = [
    [
      'text'          => getTranslation('back'),
      'callback_data' => '0:history:' . $current_year_month
    ],
    [
      'text'          => getTranslation('abort'),
      'callback_data' => '0:exit:0'
    ]
  ];

  // Get the inline key array.
  $keys[] = $nav_keys;

}

$tg_json = [];

// Answer callback.
$tg_json[] = answerCallbackQuery($update['callback_query']['id'], 'OK', true);

// Edit message.
$tg_json[] = edit_message($update, $msg, $keys, false, true);

// Telegram multicurl request.
curl_json_multi_request($tg_json);
