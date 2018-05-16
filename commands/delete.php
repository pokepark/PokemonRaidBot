<?php
// Write to log.
debug_log('DELETE()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
bot_access_check($update, BOT_ACCESS);

// Build query.
$rs = my_query(
    "
    SELECT    timezone
    FROM      raids
      WHERE   id = (
                  SELECT    raid_id
                  FROM      attendance
                    WHERE   user_id = {$update['message']['from']['id']}
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
                'text'          => getTranslation('delete'),
                'callback_data' => $raid['id'] . ':raids_delete:0'
            ]
        ]
    ];

    // Get message.
    $msg = show_raid_poll_small($raid);

    // Send message.
    send_message($update['message']['from']['id'], $msg, $keys, ['reply_markup' => ['selective' => true, 'one_time_keyboard' => true]]);
}
    
// Send message if no active raids were found.
if($count == 0) {
    // Set message.
    $msg = '<b>' . getTranslation('no_active_raids_found') . '</b>';
    
    // Send message.
    sendMessage($update['message']['from']['id'], $msg);
}

exit;
