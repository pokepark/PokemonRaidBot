<?php
// Write to log.
debug_log('raids_list()');

// For debug.
//debug_log($update);
//debug_log($data);

// Get ID.
$id = $data['id'];

// Get raid details.
if($id != 0) {
    $raid = get_raid($id);

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
                'callback_data' => $raid['id'] . ':raid_edit_poke:' . $raid['pokemon'],
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
    $keys_share = share_raid_keys($raid['id'], $update['callback_query']['from']['id']);
    $keys = array_merge($keys, $keys_share);

    // Exit key
    $empty_exit_key = [];
    $key_exit = universal_key($empty_exit_key, '0', 'exit', '1', getTranslation('done'));
    $keys = array_merge($keys, $key_exit);

    // Get message.
    $msg = show_raid_poll_small($raid);

// Get last 20 active raids.
} else {
    // Get timezone.
    $tz = TIMEZONE;

    // Build query.
    $rs = my_query(
        "
        SELECT     raids.*,
                   gyms.lat, gyms.lon, gyms.address, gyms.gym_name, gyms.ex_gym,
                   users.name,
                   UNIX_TIMESTAMP(start_time)                      AS ts_start,
                   UNIX_TIMESTAMP(end_time)                        AS ts_end,
                   UNIX_TIMESTAMP(NOW())                           AS ts_now,
                   UNIX_TIMESTAMP(end_time)-UNIX_TIMESTAMP(NOW())  AS t_left
        FROM       raids
        LEFT JOIN  gyms
        ON         raids.gym_id = gyms.id
        LEFT JOIN  users
        ON         raids.user_id = users.user_id
        WHERE      raids.end_time>NOW()
        AND        raids.timezone='{$tz}'
        ORDER BY   raids.end_time ASC LIMIT 20
        "
    );

    // Count results.
    $count = 0;

    // Init text and keys.
    $text = '';
    $keys = [];

    // Get raids.
    while ($raid = $rs->fetch_assoc()) {
        // Set text and keys.
        $gym_name = $raid['gym_name'];
        if ( empty( $gym_name ) )
          $gym_name = '';

        $text .= $gym_name . CR;
        $raid_day = unix2tz($raid['ts_start'], $raid['timezone'], 'Y-m-d');
        $today = unix2tz($raid['ts_now'], $raid['timezone'], 'Y-m-d');
        $text .= get_local_pokemon_name($raid['pokemon']) . SP . '—' . SP . (($raid_day == $today) ? '' : ($raid_day . ', ')) . unix2tz($raid['ts_start'], $raid['timezone']) . SP . getTranslation('to') . SP . unix2tz($raid['ts_end'], $raid['timezone']) . CR . CR;
        $keys[] = array(
            'text'          => $gym_name,
            'callback_data' => $raid['id'] . ':raids_list:0'
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
}

// Build callback message string.
$callback_response = 'OK';

// Answer callback.
answerCallbackQuery($update['callback_query']['id'], $callback_response);

// Edit message.
edit_message($update, $msg, $keys, false);

// Exit.
exit();
