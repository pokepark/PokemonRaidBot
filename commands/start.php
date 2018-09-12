<?php
// Write to log.
debug_log('START()');

// For debug.
//debug_log($update);
//debug_log($data);

// Get the keys.
$keys = raid_edit_gyms_first_letter_keys();

// Set message.
$msg = '<b>' . getTranslation('select_gym_first_letter') . '</b>' . (RAID_VIA_LOCATION == true ? (CR2 . CR .  getTranslation('send_location')) : '');

// Send message.
send_message($update['message']['chat']['id'], $msg, $keys, ['reply_markup' => ['selective' => true, 'one_time_keyboard' => true]]);

exit;
