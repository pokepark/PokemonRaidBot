<?php
// Write to log.
debug_log('raid_by_gym_letter()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
bot_access_check($update, 'create');

// Get the keys.
$keys = raid_edit_gyms_first_letter_keys();

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
$tg_json[] = edit_message($update,'<b>' . getTranslation('select_gym_first_letter') . '</b>', $keys, false, true);

// Telegram multicurl request.
curl_json_multi_request($tg_json);

// Exit.
exit();
