<?php
// Write to log.
debug_log('list_by_gym_letter()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
bot_access_check($update, 'list');

// Get the keys.
$keys = raid_edit_gyms_first_letter_keys('list_by_gym');

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

// Build callback message string.
$callback_response = getTranslation('select_gym');

// Telegram JSON array.
$tg_json = array();

// Answer callback.
$tg_json[] = answerCallbackQuery($update['callback_query']['id'], $callback_response, true);

// Edit the message.
$msg = '<b>' . getTranslation('list_all_active_raids') . '</b>' . CR;
$msg .= '<b>' . getTranslation('select_gym_first_letter') . '</b>' . CR;
$tg_json[] = edit_message($update, $msg, $keys, false, true);

// Telegram multicurl request.
curl_json_multi_request($tg_json);

// Exit.
exit();
