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
$msg .= CR.CR.get_user($user_id, false);

// Init empty keys array.
$keys = [];
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
if ($config->RAID_AUTOMATIC_ALARM == false) {
    $q_user = my_query("SELECT auto_alarm FROM users WHERE user_id = '{$user_id}' LIMIT 1");
    $alarm_status = $q_user->fetch()['auto_alarm'];
    $keys[] = [
        [
            'text'          => ($alarm_status == 1 ? getTranslation('switch_alarm_off') . ' ' . EMOJI_NO_ALARM : getTranslation('switch_alarm_on') . ' ' . EMOJI_ALARM),
            'callback_data' => '0:trainer:a'
        ]
    ];
}
if ($config->LANGUAGE_PRIVATE == '') {
    $keys[] = [
        [
            'text'          => getTranslation('bot_lang'),
            'callback_data' => '0:bot_lang:0'
        ]
    ];
}

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
