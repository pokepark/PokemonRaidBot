<?php
// Write to log.
debug_log('HISTORY');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
bot_access_check($update, 'history');

// Expected callback data: [Date, YYYY-MM-DD]:history_gyms:[GYM_LETTER]

$current_date = $data['id'];
$first = $data['arg'];

$split_date = explode('-', $current_date);
$current_day = $split_date[2];
$current_year_month = $split_date[0].'-'.$split_date[1];

// Length of first letter.
// Fix chinese chars, prior: $first_length = strlen($first);
$first_length = strlen(utf8_decode($first));

// Special/Custom gym letters?
$not = '';
if(!empty($config->RAID_CUSTOM_GYM_LETTERS) && $first_length == 1) {
    // Explode special letters.
    $special_keys = explode(',', $config->RAID_CUSTOM_GYM_LETTERS);

    foreach($special_keys as $id => $letter)
    {
        $letter = trim($letter);
        debug_log($letter, 'Special gym letter:');
        // Fix chinese chars, prior: $length = strlen($letter);
        $length = strlen(utf8_decode($letter));
        $not .= SP . "AND UPPER(LEFT(gym_name, " . $length . ")) != UPPER('" . $letter . "')" . SP;
    }
}

$query_collate = "";
if($config->MYSQL_SORT_COLLATE != "") {
    $query_collate = "COLLATE " . $config->MYSQL_SORT_COLLATE;
}
// Get gyms from database
$rs = my_query(
    '
    SELECT    gyms.id, gyms.gym_name, gyms.ex_gym
    FROM      gyms
    LEFT JOIN raids
    ON        raids.gym_id = gyms.id
    LEFT JOIN attendance
    ON        attendance.raid_id = raids.id
    WHERE     UPPER(LEFT(gym_name, ' . $first_length . ')) = UPPER("' . $first . '")
    AND       date_format(start_time, "%Y-%m-%d") =  "' . $current_date . '"
    AND       raids.end_time < UTC_TIMESTAMP()
    AND       attendance.id IS NOT NULL
    ' . $not . '
    GROUP BY  gym_name, raids.gym_id, gyms.id, gyms.ex_gym
    ORDER BY  gym_name ' . $query_collate 
    
);

while ($gym = $rs->fetch()) {
    // Show Ex-Gym-Marker?
    if($config->RAID_CREATION_EX_GYM_MARKER && $gym['ex_gym'] == 1) {
        $ex_raid_gym_marker = (strtolower($config->RAID_EX_GYM_MARKER) == 'icon') ? EMOJI_STAR : $config->RAID_EX_GYM_MARKER;
        $gym_name = $ex_raid_gym_marker . SP . $gym['gym_name'];
    } else {
        $gym_name = $gym['gym_name'];
    }
    $keys[][] = [
        'text'          => $gym_name,
        'callback_data' => $current_date . '/' . $first . ':history_raids:' . $gym['id']
    ];
}
$nav_keys = [
        [
        'text'          => getTranslation('back'),
        'callback_data' => $current_day.':history:' . $current_year_month
        ],
        [
        'text'          => getTranslation('abort'),
        'callback_data' => '0:exit:0'
        ]
];
$keys[] = $nav_keys;

$tg_json = [];

$tg_json[] = answerCallbackQuery($update['callback_query']['id'], 'OK', true);

$msg = getTranslation('history_title') . CR . CR;
$msg.= '<b>' . getTranslation('date') . ':</b> ' . getTranslation('month_' . substr($current_year_month,5)) . ' ' . $current_day . CR . CR;
$msg.= getTranslation('select_gym_name');

$tg_json[] = edit_message($update, $msg, $keys, false, true);

curl_json_multi_request($tg_json);

exit();

?>