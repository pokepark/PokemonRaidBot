<?php
// Write to log.
debug_log('raids_list()');

// For debug.
//debug_log($update);
//debug_log($data);

// Build query.
$rs = my_query(
    "
    SELECT    timezone
    FROM      raids
      WHERE   id = (
                  SELECT    raid_id
                  FROM      attendance
                    WHERE   user_id = {$update['callback_query']['from']['id']}
                  ORDER BY  id DESC LIMIT 1
              )
    "
);

// Get row.
$row = $rs->fetch_assoc();

// No data found.
if (!$row) {
    //sendMessage($update['message']['from']['id'], 'Can\'t determine your location, please participate in at least 1 raid');
    //exit;
    $tz = TIMEZONE;
} else {
    $tz = $row['timezone'];
}

// Build query.
$request = my_query(
    "
    SELECT    *,
              UNIX_TIMESTAMP(end_time)                        AS ts_end,
              UNIX_TIMESTAMP(start_time)                      AS ts_start,
              UNIX_TIMESTAMP(NOW())                           AS ts_now,
              UNIX_TIMESTAMP(end_time)-UNIX_TIMESTAMP(NOW())  AS t_left
    FROM      raids
      WHERE   end_time>NOW()
        AND   timezone='{$tz}'
    ORDER BY  end_time ASC LIMIT 20
    "
);

// Count results.
$count = 0;

// Get raids.
while ($raid = $request->fetch_assoc()) {

    // Counter++
    $count = $count + 1;

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

    // Get message.
    $msg = show_raid_poll_small($raid);

    // Send message.
    send_message($update['callback_query']['from']['id'], $msg, $keys, ['reply_markup' => ['selective' => true, 'one_time_keyboard' => true]]);
}
    
// Set message.
if($count == 0) {
    //sendMessage($update['callback_query']['from']['id'], '<b>' . getTranslation('no_active_raids_found') . '</b>');
    $msg = '<b>' . getTranslation('no_active_raids_found') . '</b>';
} else {
    $msg = '<b>' . getTranslation('list_all_active_raids') . ':</b>';
}

// Set message.
$keys = [];

// Edit message.
edit_message($update, $msg, $keys, false);

// Build callback message string.
$callback_response = 'OK';

// Answer callback.
answerCallbackQuery($update['callback_query']['id'], $callback_response);

exit;
