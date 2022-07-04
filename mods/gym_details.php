<?php
// Write to log.
debug_log('gym_details()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
bot_access_check($update, 'gym-details');

// Get the arg.
$args = explode(',',$data['arg'],2);
$arg = $args[0];
$gymarea_id = (count($args) > 1) ? $args[1] : false;

// Get the id.
$id = $data['id'];

// ID or Arg = 0 ?
if($arg == 0 || $id == '0' || $id == '1') {
    // Get hidden gyms?
    if($id == 0) {
        $hidden = true;
    } else {
        $hidden = false;
    }

    // Get the keys.
    $keys = raid_edit_gym_keys($arg, $gymarea_id, 'gym_details', false, $hidden);

    // Set keys.
    $msg = '<b>' . getTranslation('show_gym_details') . CR . CR . getTranslation('select_gym_name') . '</b>';

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
        $nav_keys[] = universal_inner_key($nav_keys, $gymarea_id, 'gym_letter', 'gym_details', getTranslation('back'));
        $nav_keys[] = universal_inner_key($nav_keys, '0', 'exit', '0', getTranslation('abort'));
        $nav_keys = inline_key_array($nav_keys, 2);
        // Merge keys.
        $keys = array_merge($keys, $nav_keys);
    }

// Get gym info.
} else {
    $gym = get_gym($arg);
    $msg = get_gym_details($gym, true);

    $keys = edit_gym_keys($update, $arg, $gym['show_gym'], $gym['ex_gym'], $gym['gym_note'], $gym['address']);
}

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
