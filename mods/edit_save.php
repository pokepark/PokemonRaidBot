<?php
// Write to log.
debug_log('edit_save()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
bot_access_check($update, 'create');

// Set the id and arg.
if(substr_count($data['id'], ',') == 1) {

    $idval = explode(',', $data['id']);
    $id = $idval[0];
    $arg = $idval[1];
    $chat = $data['arg'];
} else {
    $id = $data['id'];
    $arg = $data['arg'];
    $chat = 0;
}

// Set the user id.
$userid = $update['callback_query']['from']['id'];

// Update only if time is not equal to RAID_DURATION
if($arg != $config->RAID_DURATION) {

    // Build query.
    my_query(
        "
        UPDATE    raids
        SET       end_time = DATE_ADD(start_time, INTERVAL {$arg} MINUTE)
          WHERE   id = {$id}
        "
    );
}

// Fast forward to raid sharing.
if(substr_count($data['id'], ',') == 1) {
    // Write to log.
    debug_log('Doing a fast forward now!');
    debug_log('Changing data array first...');

    // Reset data array
    $data = [];
    $data['id'] = $id;
    $data['action'] = 'raid_share';
    $data['arg'] = $chat;

    // Write to log.
    debug_log($data, '* NEW DATA= ');

    // Set module path by sent action name.
    $module = ROOT_PATH . '/mods/raid_share.php';

    // Write module to log.
    debug_log($module);

    // Check if the module file exists.
    if (file_exists($module)) {
        // Dynamically include module file and exit.
        include_once($module);
        exit();
    } else {
        info_log($module, 'Error! Fast forward failed as file does not exist:');
        exit();
    }
}

// Telegram JSON array.
$tg_json = array();

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
    $admin_access = bot_access_check($update, 'raid-duration', true);
    if($admin_access) {
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

    // Get raid times.
    $raid = get_raid($data['id']);

    // Get raid level.
    $raid_level = $raid['level'];
    $const = 'SHARE_CHATS_LEVEL_' . $raid_level;
    $const_chats = $config->{$const};

    // Debug.
    //debug_log($const,'CONSTANT NAME:');
    //debug_log(constant($const),'CONSTANT VALUE:');

    // Special sharing keys for raid level?
    if(!empty($const_chats)) {
        $chats = $const_chats;
        debug_log('Special sharing keys detected for raid level ' . $raid_level);
    } else {
        $chats = '';
    }

    if($raid['event']!==NULL) {
        if($raid['event_note']==NULL) {
            $event_button_text = getTranslation("event_note_add");
        }else {
            $event_button_text = getTranslation("event_note_edit");
        }
        $keys_edit_event_note = [
            [
                [
                    'text'          => $event_button_text,
                    'callback_data' => $id . ':edit_event_note:0'
                ]
            ]
        ];
        $keys = array_merge($keys, $keys_edit_event_note);
    }
    
    // Add keys to share.
    $keys_share = share_keys($id, 'raid_share', $update, $chats);
    $keys = array_merge($keys, $keys_share);

    // Build message string.
    $msg = '';
    $msg .= getTranslation('raid_saved') . CR;
    $msg .= show_raid_poll_small($raid, false) . CR;

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
    $tg_json[] = answerCallbackQuery($update['callback_query']['id'], $callback_response, true);

    // Edit message.
    $tg_json[] = edit_message($update, $msg, $keys, false, true);

} else {
    // Get raid times.
    $raid = get_raid($data['id']);

    // Get text and keys.
    $text = show_raid_poll($raid);
    $keys = keys_vote($raid);

    // Build callback message string.
    $callback_response = getTranslation('end_time') . $data['arg'] . ' ' . getTranslation('minutes');

    // Answer callback.
    $tg_json[] = answerCallbackQuery($update['callback_query']['id'], $callback_response, true);

    // Edit message.
    if($config->RAID_PICTURE) {
       send_response_vote($update, $data,false,false);
    } else {
       send_response_vote($update, $data);
    }
}

// Telegram multicurl request.
curl_json_multi_request($tg_json);

// Exit.
exit();
