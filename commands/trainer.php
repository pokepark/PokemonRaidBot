<?php
// Write to log.
debug_log('TRAINER()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
bot_access_check($update, 'trainer');

// Set message.
$msg = '<b>' . getTranslation('trainerinfo_set_yours') . '</b>';

$user_id = $update['message']['from']['id'];
$msg .= get_user($user_id);

// Init empty keys array.
$keys = [];
$keys_top_row = [];
// Create keys array.
if($config->CUSTOM_TRAINERNAME){
    $keys_top_row[] =
            [
                'text'          => getTranslation('name'),
                'callback_data' => '1:trainer_name_code:0'
            ];
}
if($config->RAID_POLL_SHOW_TRAINERCODE){
    $keys_top_row[] =
            [
                'text'          => getTranslation('name'),
                'callback_data' => '1:trainer_name_code:0'
            ];
}
$keys_team_level = [
    [
        [
            'text'          => getTranslation('team'),
            'callback_data' => '0:trainer_team:0'
        ],
        [
            'text'          => getTranslation('level'),
            'callback_data' => '0:trainer_level:0'
        ]
    ]
];
$keys = array_merge($keys_top_row,$keys_team_level);

// Check access.
$access = bot_access_check($update, 'trainer-share', true, true);

// Display sharing options for admins and users with trainer-share permissions
if($access && (is_file(ROOT_PATH . '/access/' . $access) || $access == 'BOT_ADMINS')) {
    // Add sharing keys.
    $share_keys = [];
    $share_keys[] = universal_inner_key($keys, '0', 'trainer_add', '0', getTranslation('trainer_message_share'));
    $share_keys[] = universal_inner_key($keys, '0', 'trainer_delete', '0', getTranslation('trainer_message_delete'));

    // Get the inline key array.
    $keys[] = $share_keys;

    // Add message.
    $msg .= CR . CR . getTranslation('trainer_message_share_or_delete');
}

// Add abort key.
$nav_keys = [];
$nav_keys[] = universal_inner_key($keys, '0', 'exit', '0', getTranslation('abort'));

// Get the inline key array.
$keys[] = $nav_keys;

// Send message.
send_message($update['message']['chat']['id'], $msg, $keys, ['reply_markup' => ['selective' => true, 'one_time_keyboard' => true]]);

?>
