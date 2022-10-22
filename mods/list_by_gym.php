<?php
// Write to log.
debug_log('list_by_gym()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
$botUser->accessCheck($update, 'list');

// Get the first letter
$args = explode(',',$data['arg'],2);
$first = $args[0];
$gymarea_id = (count($args) > 1) ? $args[1] : false;

// Back key id, action and arg
$back_id = 'n';
$back_action = 'list_by_gym_letter';
$back_arg = 0;

// Get the keys.
$keys = raid_edit_gym_keys($first, $gymarea_id, 'list_raid');

// No keys found.
if (!$keys) {
    // Create the keys.
    $keys = [
        [
            [
                'text'          => getTranslation('abort'),
                'callback_data' => '0:exit:0'
            ]
        ]
    ];
} else {
    // Add navigation keys.
    $nav_keys = [];
    $nav_keys[] = universal_inner_key($nav_keys, $back_id, $back_action, $back_arg, getTranslation('back'));
    $nav_keys[] = universal_inner_key($nav_keys, '0', 'exit', '0', getTranslation('abort'));
    $nav_keys = inline_key_array($nav_keys, 2);
    // Merge keys.
    $keys = array_merge($keys, $nav_keys);
}

// Build callback message string.
$callback_response = getTranslation('here_we_go');

// Telegram JSON array.
$tg_json = array();

// Answer callback.
$tg_json[] = answerCallbackQuery($update['callback_query']['id'], $callback_response, true);

// Edit the message.
$tg_json[] = edit_message($update, getTranslation('select_gym_name'), $keys, false, true);

// Telegram multicurl request.
curl_json_multi_request($tg_json);

// Exit.
exit();
