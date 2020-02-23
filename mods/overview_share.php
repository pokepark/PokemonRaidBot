<?php
// Write to log.
debug_log('overview_share()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
bot_access_check($update, 'overview');

// Get chat ID from data
$chat_id = 0;
$chat_id = $data['arg'];

// Get active raids.
$request_active_raids = my_query(
    "
    SELECT    raids.*,
              gyms.lat, gyms.lon, gyms.address, gyms.gym_name, gyms.ex_gym,
              TIME_FORMAT(TIMEDIFF(end_time, UTC_TIMESTAMP()) + INTERVAL 1 MINUTE, '%k:%i') AS t_left
    FROM      raids
    LEFT JOIN gyms
    ON        raids.gym_id = gyms.id
      WHERE   raids.end_time>UTC_TIMESTAMP()
    ORDER BY  raids.end_time ASC
    "
);

// Count active raids.
$count_active_raids = 0;

// Init empty active raids and raid_ids array.
$raids_active = [];
$raid_ids_active = [];

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
debug_log($raids_active, 'Active raids for overview:');

// Init empty active chats array.
$chats_active = [];

// Make sure we have active raids.
if ($count_active_raids > 0) {
    // Implode raid_id's of all active raids.
    $raid_ids_active = implode(',',$raid_ids_active);

    // Write to log.
    debug_log($raid_ids_active, 'IDs of active raids for overview:');

    // Get chat for active raids.
    if ($chat_id == 0) {
        $request_active_chats = my_query(
            "
            SELECT    *
            FROM      cleanup
              WHERE   raid_id IN ({$raid_ids_active})
              ORDER BY chat_id, FIELD(raid_id, {$raid_ids_active})
            "
        );
    } else {
        $request_active_chats = my_query(
            "
            SELECT    *
            FROM      cleanup
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
debug_log($chats_active, 'Active chats for overview:');

// Get raid overviews
if ($chat_id == 0) {
    get_overview($update, $chats_active, $raids_active, $action = 'share');
} else {
    get_overview($update, $chats_active, $raids_active, $action = 'share', $chat_id);
}
exit;
