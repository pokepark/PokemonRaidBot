<?php
// Write to log.
debug_log('raid_by_gym_letter()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
bot_access_check($update, 'create');

// Get the keys.
$keys_and_gymarea = raid_edit_gyms_first_letter_keys('raid_by_gym', false, (empty($data['id']) ? '' : $data['id']), 'raid_by_gym_letter');
$keys = $keys_and_gymarea['keys'];

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
$msg = '<b>' . getTranslation('select_gym_first_letter') . '</b>';
$msg.= (($keys_and_gymarea['gymarea_name'] != '') ? CR . CR . getTranslation('current_gymarea') . ': ' . $keys_and_gymarea['gymarea_name'] : '');
$tg_json[] = edit_message($update, $msg, $keys, false, true);

// Telegram multicurl request.
curl_json_multi_request($tg_json);

// Exit.
exit();
