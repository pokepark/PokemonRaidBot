<?php
// Write to log.
debug_log('gym_details()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
bot_access_check($update, 'gym-details');

// Get the arg.
$arg = $data['arg'];

// Get the id.
$id = $data['id'];

// ID = 0 ?
if($arg == 0) {
    // Get hidden gyms?
    if($id == 0) {
        $hidden = true;
    } else {
        $hidden = false;
    }

    // Get the keys.
    $keys = raid_edit_gym_keys($arg, false, 'gym_details', false, $hidden);

    // Set keys.
    $msg = '<b>' . getTranslation('show_gym_details') . SP . '—' . SP . getTranslation('select_gym_name') . '</b>';

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
        $nav_keys[] = universal_inner_key($nav_keys, '0', 'gym_letter', 'gym_details', getTranslation('back'));
        $nav_keys[] = universal_inner_key($nav_keys, '0', 'exit', '0', getTranslation('abort'));
        $nav_keys = inline_key_array($nav_keys, 2);
        // Merge keys.
        $keys = array_merge($keys, $nav_keys);
    }

// Get gym info.
} else {
    $gym = get_gym($arg);
    $msg = get_gym_details($gym, true);
    $msg .= CR . CR . '<b>' . getTranslation('change_extended_gym_details') . '</b>';

    // Hide gym?
    if($gym['show_gym'] == 1) {
        $text_show_button = getTranslation('hide_gym');
        $arg_show = 0;

    // Show gym?
    } else {
        $text_show_button = getTranslation('show_gym');
        $arg_show = 1;
    }

    // Normal gym?
    if($gym['ex_gym'] == 1) {
        $text_ex_button = getTranslation('normal_gym');
        $arg_ex = 0;

    // Ex-raid gym?
    } else {
        $text_ex_button = getTranslation('ex_gym');
        $arg_ex = 1;
    }

    // Add buttons to show/hide the gym and add/remove ex-raid flag
    $keys = [];
    $keys[] = array(
        'text'          => $text_show_button,
        'callback_data' => $arg . ':gym_edit_details:show-' . $arg_show
    );
    $keys[] = array(
        'text'          => $text_ex_button,
        'callback_data' => $arg . ':gym_edit_details:ex-' . $arg_ex
    );
    $keys[] = array(
        'text'          => getTranslation('done'),
        'callback_data' => '0:exit:1'
    );

    // Get the inline key array.
    $keys = inline_key_array($keys, 1);
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
