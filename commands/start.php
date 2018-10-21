<?php
// Write to log.
debug_log('START()');

// For debug.
//debug_log($update);
//debug_log($data);

// Get gym by name.
// Trim away everything before "/start "
$searchterm = $update['message']['text'];
$searchterm = substr($searchterm, 7);

// Get the keys by gym name search.
if(!empty($searchterm)) {
    $keys = raid_get_gyms_list_keys($searchterm);
} 

// Get the keys if nothing was returned. 
if(!$keys) {
    $keys = raid_edit_gyms_first_letter_keys();
}

// No keys found.
if (!$keys) {
    // Create the keys.
    $keys = [
        [
            [
                'text'          => getTranslation('not_supported'),
                'callback_data' => '0:exit:0'
            ]
        ]
    ];
}

// Set message.
$msg = '<b>' . getTranslation('select_gym_first_letter') . '</b>' . (RAID_VIA_LOCATION == true ? (CR2 . CR .  getTranslation('send_location')) : '');

// Send message.
send_message($update['message']['chat']['id'], $msg, $keys, ['reply_markup' => ['selective' => true, 'one_time_keyboard' => true]]);

?>
