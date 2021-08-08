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

$user_id = $update['callback_query']['from']['id'];
$msg .= CR.CR.get_user($user_id, false);

// Init empty keys array.
$keys = [];
$keys_top_row = [];
// Create keys array.
if($config->CUSTOM_TRAINERNAME){
    $keys[0][] =
            [
                'text'          => getTranslation('name'),
                'callback_data' => '0:trainer_name:0'
            ];
}
if($config->RAID_POLL_SHOW_TRAINERCODE){
    $keys[0][] =
            [
                'text'          => getTranslation('trainercode'),
                'callback_data' => '0:trainer_code:0'
            ];
}
$keys[] = [
        [
            'text'          => getTranslation('team'),
            'callback_data' => '0:trainer_team:0'
        ],
        [
            'text'          => getTranslation('level'),
            'callback_data' => '0:trainer_level:0'
        ]
];

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

// Answer callback.
answerCallbackQuery($update['callback_query']['id'], 'OK');

// Edit message.
edit_message($update, $msg, $keys, false);

?>
