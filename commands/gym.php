<?php
// Write to log.
debug_log('GYM');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
bot_access_check($update, 'gym-details');

// Set message.
$msg = '<b>' . getTranslation('show_gym_details') . SP . '—' . SP . getTranslation('select_gym_first_letter') . '</b>';

// Set keys.
$keys = raid_edit_gyms_first_letter_keys('gym_details');

// Add key for hidden gyms.
$h_keys = [];
$h_keys[] = universal_inner_key($h_keys, '0', 'gym_hidden_letter', 'gym_details', getTranslation('hidden_gyms'));
$h_keys = inline_key_array($h_keys, 1);

// Merge keys.
$keys = array_merge($h_keys, $keys);

// Send message.
send_message($update['message']['chat']['id'], $msg, ['inline_keyboard' => $keys, 'selective' => true, 'one_time_keyboard' => true], ['disable_web_page_preview' => 'true']);

?>
