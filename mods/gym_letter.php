<?php
// Write to log.
debug_log('gym_letter()');

// For debug.
//debug_log($update);
//debug_log($data);

// Get the arg.
$arg = $data['arg'];

// Check access, show message and set keys based on arg.
if($arg == 'gym_delete') {
    // Check access.
    bot_access_check($update, 'gym-delete');

    // Set message.
    $msg = '<b>' . getTranslation('gym_delete') . SP . '—' . SP . getTranslation('select_gym_first_letter') . '</b>';
} else {
    // Force set arg.
    $arg = 'gym_details';

    // Check access.
    bot_access_check($update, 'gym-details');

    // Set message.
    $msg = '<b>' . getTranslation('show_gym_details') . SP . '—' . SP . getTranslation('select_gym_first_letter') . '</b>';
}

// Set keys.
$keys = raid_edit_gyms_first_letter_keys($arg);

// Add key for hidden gyms.
$h_keys = [];
$h_keys[] = universal_inner_key($h_keys, '0', 'gym_hidden_letter', $arg, getTranslation('hidden_gyms'));
$h_keys = inline_key_array($h_keys, 1);

// Merge keys.
$keys = array_merge($h_keys, $keys);

// Build callback message string.
$callback_response = getTranslation('here_we_go');

// Telegram JSON array.
$tg_json = array();

// Answer callback.
$tg_json[] = answerCallbackQuery($update['callback_query']['id'], $callback_response, true);

// Edit the message.
$tg_json[] = edit_message($update, $msg, $keys, ['disable_web_page_preview' => 'true'], true);

// Telegram multicurl request.
curl_json_multi_request($tg_json);

// Exit.
exit();

?>
