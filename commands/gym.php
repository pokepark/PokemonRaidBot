<?php
// Write to log.
debug_log('GYM');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
$botUser->accessCheck($update, 'gym-details');

// Set keys.
$keys_and_gymarea = raid_edit_gyms_first_letter_keys('gym_details', false, false, 'gym_letter');
$keys = $keys_and_gymarea['keys'];

// Set message.
$msg = '<b>' . getTranslation('show_gym_details') . CR . CR;
if($config->ENABLE_GYM_AREAS) {
    if($keys_and_gymarea['gymarea_name'] == '') {
        $msg .= getTranslation('select_gym_area') . '</b>' . CR;
    }elseif($config->DEFAULT_GYM_AREA !== false) {
        if($keys_and_gymarea['letters']) {
            $msg .= getTranslation('select_gym_first_letter_or_gym_area') . '</b>' . CR;
        }else {
            $msg .= getTranslation('select_gym_name_or_gym_area') . '</b>' . CR;
        }
    }else {
        if($keys_and_gymarea['letters']) {
            $msg .= getTranslation('select_gym_first_letter') . '</b>' . CR;
        }else {
            $msg .= getTranslation('select_gym_name') . '</b>' . CR;
        }
    }
}else {
    if($keys_and_gymarea['letters']) {
        $msg .= getTranslation('select_gym_first_letter') . '</b>' . CR;
    }else {
        $msg .= getTranslation('select_gym_name') . '</b>' . CR;
    }
}
$msg.= (($keys_and_gymarea['gymarea_name'] != '') ? CR . CR . getTranslation('current_gymarea') . ': ' . $keys_and_gymarea['gymarea_name'] : '');

// Add key for hidden gyms.
$h_keys = [];
if($config->ENABLE_GYM_AREAS == false or ($config->ENABLE_GYM_AREAS == true && $config->DEFAULT_GYM_AREA !== false)) {
    // Add key for hidden gyms.
    $h_keys[] = universal_inner_key($h_keys, '0', 'gym_hidden_letter', 'gym_details', getTranslation('hidden_gyms'));
    $h_keys = inline_key_array($h_keys, 1);
}

// Merge keys.
$keys = array_merge($h_keys, $keys);

if($botUser->accessCheck($update, 'gym-add')) {
    $keys[] = [
        [
            'text'          => getTranslation('gym_create'),
            'callback_data' => '0:gym_create:0'
        ]
    ];
}

$keys[] = [
    [
        'text'          => getTranslation('abort'),
        'callback_data' => '0:exit:0'
    ]
];

// Send message.
send_message($update['message']['chat']['id'], $msg, $keys, ['reply_markup' => ['selective' => true, 'one_time_keyboard' => true], 'disable_web_page_preview' => 'true']);

?>
