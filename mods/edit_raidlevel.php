<?php
// Write to log.
debug_log('edit_raidlevel()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
bot_access_check($update, 'create');

// Get gym data via ID in arg
$gym_id = $data['arg'];
$gym = get_gym($gym_id);

// Back key id, action and arg
$back_id = 0;
$back_action = 'raid_by_gym';
$back_arg = $data['id'];
$gym_first_letter = $back_arg;

// Telegram JSON array.
$tg_json = array();

// Active raid?
$duplicate_id = active_raid_duplication_check($gym_id);
if ($duplicate_id > 0) {
    $keys = [];
    $raid_id = $duplicate_id;
    $raid = get_raid($raid_id);
    $msg = EMOJI_WARN . SP . getTranslation('raid_already_exists') . SP . EMOJI_WARN . CR . show_raid_poll_small($raid);

    // Check if the raid was already shared.
    $rs_share = my_query(
        "   
        SELECT  COUNT(*) AS raid_count
        FROM    cleanup
        WHERE   raid_id = '{$raid_id}'
        "
    );

    $shared = $rs_share->fetch_assoc();

    // Add keys for sharing the raid.
    if($shared['raid_count'] == 0) {
        $keys = share_keys($raid_id, 'raid_share', $update);

        // Exit key
        $empty_exit_key = [];
        $key_exit = universal_key($empty_exit_key, '0', 'exit', '0', getTranslation('abort'));
        $keys = array_merge($keys, $key_exit);
    }

    // Answer callback.
    $tg_json[] = answerCallbackQuery($update['callback_query']['id'], getTranslation('raid_already_exists'), true);

    // Edit the message.
    $tg_json[] = edit_message($update, $msg, $keys, false, true);

    // Telegram multicurl request.
    curl_json_multi_request($tg_json);

    // Exit.
    exit();
}

// Check access - user must be admin for raid_level X
$admin_access = bot_access_check($update, 'ex-raids', true);
if ($admin_access) {
    // Get the keys.
    $keys = raid_edit_raidlevel_keys($gym_id, $gym_first_letter, true);
} else {
    // Get the keys.
    $keys = raid_edit_raidlevel_keys($gym_id, $gym_first_letter);
}

// No keys found.
if (!$keys) {
    // Create the keys.
    $keys = [
        [
            [
                'text'          => getTranslation('abort'),
                'callback_data' => '0:exit:0'
            ]
        ]
    ];
} else {
    // Add navigation keys.
    $nav_keys = [];
    $nav_keys[] = universal_inner_key($nav_keys, $back_id, $back_action, $back_arg, getTranslation('back'));
    $nav_keys[] = universal_inner_key($nav_keys, $gym_id, 'exit', '2', getTranslation('abort'));
    $nav_keys = inline_key_array($nav_keys, 2);
    // Merge keys.
    $keys = array_merge($keys, $nav_keys);
}

// Build message.
$msg = getTranslation('create_raid') . ': <i>' . $gym['address'] . '</i>';

// Build callback message string.
$callback_response = getTranslation('gym_saved');

// Answer callback.
$tg_json[] = answerCallbackQuery($update['callback_query']['id'], $callback_response, true);

// Edit the message.
$tg_json[] = edit_message($update, $msg . CR . getTranslation('select_raid_level') . ':', $keys, false, true);

// Telegram multicurl request.
curl_json_multi_request($tg_json);

// Exit.
exit();

