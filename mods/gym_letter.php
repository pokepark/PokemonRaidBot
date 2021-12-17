<?php
// Write to log.
debug_log('gym_letter()');

// For debug.
//debug_log($update);
//debug_log($data);

// Get the arg.
$arg = $data['arg'];

// Set keys.
$keys_and_gymarea = raid_edit_gyms_first_letter_keys($arg, false, $data['id'], 'gym_letter', 'gym_details');
$keys = $keys_and_gymarea['keys'];

// Check access, show message and set keys based on arg.
if($arg == 'gym_delete') {
    // Check access.
    bot_access_check($update, 'gym-delete');

    // Set message.
    $msg = '<b>' . getTranslation('gym_delete') . CR . getTranslation('select_gym_first_letter') . '</b>';
    $msg.= (($keys_and_gymarea['gymarea_name'] != '') ? CR . CR . getTranslation('current_gymarea') . ': ' . $keys_and_gymarea['gymarea_name'] : '');
} else {
    // Force set arg.
    $arg = 'gym_details';

    // Check access.
    bot_access_check($update, 'gym-details');

    // Set message.
    $msg = '<b>' . getTranslation('show_gym_details') . CR . getTranslation('select_gym_first_letter') . '</b>';
    $msg.= (($keys_and_gymarea['gymarea_name'] != '') ? CR . CR . getTranslation('current_gymarea') . ': ' . $keys_and_gymarea['gymarea_name'] : '');
}

$nav_keys = [];

if($data['id'] != 0) {
    $nav_keys[] = [
        'text' => getTranslation('back'),
        'callback_data' => '0:gym_letter:gym_details'
    ];
    // Add key for hidden gyms.
    $h_keys = [];
    $h_keys[] = universal_inner_key($h_keys, $data['id'], 'gym_hidden_letter', $arg, getTranslation('hidden_gyms'));
    $h_keys = inline_key_array($h_keys, 1);
    // Merge keys.
    $keys = array_merge($h_keys, $keys);
}
$nav_keys[] = [
        'text' => getTranslation('abort'),
        'callback_data' => '0:exit:0'
    ];
$nav_keys = inline_key_array($nav_keys, 2);
// Merge keys.
$keys = array_merge($keys, $nav_keys);

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
