<?php
// Write to log.
debug_log('LIST');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
if(!isset($skip_access) or $skip_access != true) bot_access_check($update, 'list');

// Set message.
$msg = '<b>' . getTranslation('list_all_active_raids') . '</b>' . CR;
$msg .= '<b>' . getTranslation('select_gym_first_letter') . '</b>' . CR;

// Set keys.
$keys = raid_edit_gyms_first_letter_keys('list_by_gym');

// Send message.
send_message($update['message']['chat']['id'], $msg, $keys, ['reply_markup' => ['selective' => true, 'one_time_keyboard' => true], 'disable_web_page_preview' => 'true']);

?>
