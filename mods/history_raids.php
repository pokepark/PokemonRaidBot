<?php
// Write to log.
debug_log('HISTORY');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
bot_access_check($update, 'history');

// Expected callback data: [Date, YYYY-MM-DD]/[GYM_LETTER]:history_raids:[GYM_ID]

$id_data = explode('/',$data['id']);
$current_date = $id_data[0];
$gym_first_letter = $id_data[1];

$gym_id = $data['arg'];

// Get raids from database
$rs = my_query(
    '
    SELECT    gyms.gym_name, raids.id, raids.start_time, raids.pokemon, raids.pokemon_form
    FROM      gyms
    LEFT JOIN raids
    ON        raids.gym_id = gyms.id
    LEFT JOIN attendance
    ON        attendance.raid_id = raids.id
    WHERE     gyms.id = "'.$gym_id.'"
    AND       raids.end_time < UTC_TIMESTAMP()
    AND       attendance.id IS NOT NULL
    GROUP BY  raids.id, raids.start_time, raids.pokemon, raids.pokemon_form, gyms.gym_name
    ORDER BY  start_time
    '
);
while ($raid = $rs->fetch()) {
    $keys[][] = [
        'text'          => dt2time($raid['start_time']) . ': ' . get_local_pokemon_name($raid['pokemon'],$raid['pokemon_form']),
        'callback_data' => $data['id'] . ':history_raid:' . $gym_id .'/' . $raid['id']
    ];
    $gym_name = $raid['gym_name'];
    $start_time = $raid['start_time'];
}
$nav_keys = [
        [
        'text'          => getTranslation('back'),
        'callback_data' => $current_date . ':history_gyms:' . $gym_first_letter
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
$msg.= '<b>' . getTranslation('date') . ':</b> ' . getTranslation('month_' . substr($current_date,5,2)) . ' ' . substr($current_date,8) . CR;
$msg.= '<b>' . getTranslation('gym') . ':</b> ' . $gym_name . CR . CR;
$msg.= getTranslation('history_select_raid').':';

$tg_json[] = edit_message($update, $msg, $keys, false, true);

curl_json_multi_request($tg_json);

exit();

?>