<?php
// Write to log.
debug_log('edit_save()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check raid access.
raid_access_check($update, $data);

// Set the id.
$id = $data['id'];

// Set the arg.
$arg = $data['arg'];

// Set the user id.
$userid = $update['callback_query']['from']['id'];

// Update only if time is not equal to RAID_POKEMON_DURATION_SHORT
if($arg != RAID_POKEMON_DURATION_SHORT) {

    // Build query.
    my_query(
        "
        UPDATE    raids
        SET       end_time = DATE_ADD(start_time, INTERVAL {$data['arg']} MINUTE)
          WHERE   id = {$id}
        "
    );
}

// Build msg.
if ($update['callback_query']['message']['chat']['type'] == 'private') {
    // Init keys.
    $keys = [];

    // Add delete to keys.
    $keys = [
        [
            [
                'text'          => getTranslation('delete'),
                'callback_data' => $id . ':raids_delete:0'
            ]
        ]
    ];

    // Check access level prior allowing to change raid time
    $admin_access = bot_access_check($update, BOT_ADMINS, true);
    if($admin_access && $arg == RAID_POKEMON_DURATION_SHORT) {
        // Add time change to keys.
        $keys_time = [
            [
                [
                    'text'          => getTranslation('change_raid_duration'),
                    'callback_data' => $id . ':edit_time:0,0,more,1'
                ]
            ]
        ];
        $keys = array_merge($keys, $keys_time);
    }

    // Add keys to share.
    $keys_share = share_raid_keys($id, $userid);
    $keys = array_merge($keys, $keys_share);

    // Get raid times.
    $raid = get_raid($data['id']);

    // Build message string.
    $msg = '';
    $msg .= getTranslation('raid_saved') . CR;
    $msg .= show_raid_poll_small($raid) . CR;

    // User_id tag.
    $user_id_tag = '#' . $update['callback_query']['from']['id'];

    // Gym Name
    if(!empty($raid['gym_name']) && ($raid['gym_name'] != $user_id_tag)) {
	$msg .= getTranslation('set_gym_team') . CR2;
    } else {
        $msg .= getTranslation('set_gym_name_and_team') . CR2;
        $msg .= getTranslation('set_gym_name_command') . CR;
    }
    $msg .= getTranslation('set_gym_team_command');

    // Build callback message string.
    $callback_response = getTranslation('end_time') . $data['arg'] . ' ' . getTranslation('minutes');

    // Answer callback.
    answerCallbackQuery($update['callback_query']['id'], $callback_response);

    // Edit message.
    edit_message($update, $msg, $keys, false);

} else {
    // Get raid times.
    $raid = get_raid($data['id']);

    // Get text and keys.
    $text = show_raid_poll($raid);
    $keys = keys_vote($raid);

    // Build callback message string.
    $callback_response = getTranslation('end_time') . $data['arg'] . ' ' . getTranslation('minutes');

    // Answer callback.
    answerCallbackQuery($update['callback_query']['id'], $callback_response);

    // Edit message.
    edit_message($update, $text, $keys, false);
}

// Exit.
exit();
