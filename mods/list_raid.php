<?php
// Write to log.
debug_log('list_raid()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
bot_access_check($update, 'list');

// Get gym ID.
$gym_id = $data['arg'];
$raid_id = $data['id'];

// Get raid details.
if($raid_id != 0) $sql_condition = 'AND raids.id = ' . $raid_id . ' LIMIT 1';
else $sql_condition = 'AND  gyms.id = ' . $gym_id;
$rs = my_query(
    "
    SELECT     raids.*,
               gyms.lat, gyms.lon, gyms.address, gyms.gym_name, gyms.ex_gym, gyms.gym_note,
               users.name,
               events.name as event_name, events.description as event_description, events.vote_key_mode as event_vote_key_mode, events.time_slots as event_time_slots, events.raid_duration as event_raid_duration, events.hide_raid_picture as event_hide_raid_picture,
               TIME_FORMAT(TIMEDIFF(end_time, UTC_TIMESTAMP()) + INTERVAL 1 MINUTE, '%k:%i') AS t_left
    FROM       raids
    LEFT JOIN  gyms
    ON         raids.gym_id = gyms.id
    LEFT JOIN  users
    ON         raids.user_id = users.user_id
    LEFT JOIN  events
    ON         events.id = raids.event
    WHERE      end_time > UTC_TIMESTAMP() - INTERVAL 10 MINUTE
    {$sql_condition}
    "
);
if($rs->rowcount() == 1) {
    // Get the row.
    $raid = $rs->fetch();

    debug_log($raid);

    // Create keys array.
    $keys = [
        [
            [
                'text'          => getTranslation('expand'),
                'callback_data' => $raid['id'] . ':vote_refresh:0',
            ]
        ],
        [
            [
                'text'          => getTranslation('update_pokemon'),
                'callback_data' => $raid['id'] . ':raid_edit_poke:' . $raid['level'],
            ]
        ],
        [
            [
                'text'          => getTranslation('delete'),
                'callback_data' => $raid['id'] . ':raids_delete:0'
            ]
        ]
    ];

    // Add keys to share.
    debug_log($raid, 'raw raid data for share: ');
    $keys_share = share_keys($raid['id'], 'raid_share', $update, '', '', false, $raid['level']));
    if(is_array($keys_share)) {
        $keys = array_merge($keys, $keys_share);
    } else {
        debug_log('There are no groups to share to, is SHARE_CHATS set?');
    }
    // Exit key
    $empty_exit_key = [];
    $key_exit = universal_key($empty_exit_key, '0', 'exit', '1', getTranslation('done'));
    $keys = array_merge($keys, $key_exit);

    // Get message.
    $msg = show_raid_poll_small($raid);

}else {
    $raids = $rs->fetchAll();
    $msg = getTranslation('list_all_active_raids').':'. CR;
    $keys = [];
    $i = 1;
    foreach($raids as $raid) {
        $raid_pokemon_name = get_local_pokemon_name($raid['pokemon'], $raid['pokemon_form']);
        $msg .= '<b>' . $i .'. ' . $raid_pokemon_name . '</b>' . CR;
        if(!empty($raid['event_name'])) $msg .= $raid['event_name'] . CR;
        $msg .= get_raid_times($raid,false, true) . CR . CR;
        $keys[] = [
            [
                'text'          => $i . '. ' . $raid_pokemon_name,
                'callback_data' => $raid['id'] . ':list_raid:0'
            ]
        ];
        $i++;
    }
    $keys[] = [
        [
            'text'          => getTranslation('back'),
            'callback_data' => '0:list_by_gym:' . $raid['gym_name'][0]
        ]
    ];
}

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

// Exit.
exit();
