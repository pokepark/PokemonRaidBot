<?php
// Write to log.
debug_log('GYM');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
bot_access_check($update, 'gym-details');

// Set keys.
$keys_and_gymarea = raid_edit_gyms_first_letter_keys('gym_details', false, false, 'gym_letter');
$keys = $keys_and_gymarea['keys'];

// Set message.
$msg = '<b>' . getTranslation('show_gym_details') . CR . CR . getTranslation('select_gym_first_letter') . '</b>';
$msg.= (($keys_and_gymarea['gymarea_name'] != '') ? CR . CR . getTranslation('current_gymarea') . ': ' . $keys_and_gymarea['gymarea_name'] : '');

// Add key for hidden gyms.
$h_keys = [];
$h_keys[] = universal_inner_key($h_keys, '0', 'gym_hidden_letter', 'gym_details', getTranslation('hidden_gyms'));
$h_keys = inline_key_array($h_keys, 1);

// Merge keys.
$keys = array_merge($h_keys, $keys);

// Send message.
send_message($update['message']['chat']['id'], $msg, $keys, ['reply_markup' => ['selective' => true, 'one_time_keyboard' => true], 'disable_web_page_preview' => 'true']);

?>
