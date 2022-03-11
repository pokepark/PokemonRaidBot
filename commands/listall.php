<?php
// Write to log.
debug_log('LIST');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
if(!isset($skip_access) or $skip_access != true) bot_access_check($update, 'list');

// Set keys.
$keys_and_gymarea = raid_edit_gyms_first_letter_keys('list_by_gym', false, false, 'listall', 'list_raid');
$keys = $keys_and_gymarea['keys'];
$keys[] = [
    [
        'text'          => getTranslation('abort'),
        'callback_data' => '0:exit:0'
    ]
];

// Set message.
$msg = '<b>' . getTranslation('list_all_active_raids') . '</b>' . CR;
$msg.= (($keys_and_gymarea['gymarea_name'] != '') ? getTranslation('current_gymarea') . ': ' . $keys_and_gymarea['gymarea_name'] . CR: '');
if($config->ENABLE_GYM_AREAS) {
    if($keys_and_gymarea['gymarea_name'] == '') {
        $msg .= '<b>' . getTranslation('select_gym_area') . '</b>' . CR;
    }else {
        if($keys_and_gymarea['letters']) {
            $msg .= '<b>' . getTranslation('select_gym_first_letter_or_gym_area') . '</b>' . CR;
        }else {
            $msg .= '<b>' . getTranslation('select_gym_name_or_gym_area') . '</b>' . CR;
        }
    }
}elseif($keys_and_gymarea['letters']) {
    $msg .= '<b>' . getTranslation('select_gym_first_letter') . '</b>' . CR;
}else {
    $msg .= '<b>' . getTranslation('select_gym_name') . '</b>' . CR;
}

// Send message.
send_message($update['message']['chat']['id'], $msg, $keys, ['reply_markup' => ['selective' => true, 'one_time_keyboard' => true], 'disable_web_page_preview' => 'true']);

?>
