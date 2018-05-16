<?php
// Write to log.
debug_log('overview_share()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
bot_access_check($update, BOT_ADMINS);

// Get chat ID from data
$chat_id = 0;
$chat_id = $data['arg'];

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

// Get active raids.
$request_active_raids = my_query(
    "
    SELECT    *,
              UNIX_TIMESTAMP(end_time)                        AS ts_end,
              UNIX_TIMESTAMP(start_time)                      AS ts_start,
              UNIX_TIMESTAMP(NOW())                           AS ts_now,
              UNIX_TIMESTAMP(end_time)-UNIX_TIMESTAMP(NOW())  AS t_left
    FROM      raids
      WHERE   end_time>NOW()
        AND   timezone='{$tz}'
    ORDER BY  end_time ASC
    "
);

// Count active raids.
$count_active_raids = 0;

// Init empty active raids and raid_ids array.
$raids_active = array();
$raid_ids_active = array();

// Get all active raids into array.
while ($rowRaids = $request_active_raids->fetch_assoc()) {
    // Use current raid_id as key for raids array
    $current_raid_id = $rowRaids['id'];
    $raids_active[$current_raid_id] = $rowRaids;

    // Build array with raid_ids to query cleanup table later
    $raid_ids_active[] = $rowRaids['id'];

    // Counter for active raids
    $count_active_raids = $count_active_raids + 1;
}

// Write to log.
debug_log('Active raids:');
debug_log($raids_active);

// Init empty active chats array.
$chats_active = array();

// Make sure we have active raids.
if ($count_active_raids > 0) {
    // Implode raid_id's of all active raids.
    $raid_ids_active = implode(',',$raid_ids_active);

    // Write to log.
    debug_log('IDs of active raids:');
    debug_log($raid_ids_active);

    // Get chat for active raids.
    if ($chat_id == 0) {
        $request_active_chats = my_query(
            "
            SELECT    *
            FROM      cleanup_raids
              WHERE   raid_id IN ({$raid_ids_active})
              ORDER BY chat_id, FIELD(raid_id, {$raid_ids_active})
            "
        );
    } else {
        $request_active_chats = my_query(
            "
            SELECT    *
            FROM      cleanup_raids
              WHERE   raid_id IN ({$raid_ids_active})
	      AND     chat_id = '{$chat_id}'
              ORDER BY chat_id, FIELD(raid_id, {$raid_ids_active})
            "
        );
    }

    // Get all chats.    
    while ($rowChats = $request_active_chats->fetch_assoc()) {
        $chats_active[] = $rowChats;
    }
}

// Write to log.
debug_log('Active chats:');
debug_log($chats_active);

// Get raid overviews
if ($chat_id == 0) {
    get_overview($update, $chats_active, $raids_active, $action = 'share');
} else {
    get_overview($update, $chats_active, $raids_active, $action = 'share', $chat_id);
}
exit;
