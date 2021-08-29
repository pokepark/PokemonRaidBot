<?php
// Write to log.
debug_log('gym_delete()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
bot_access_check($update, 'gym-delete');

// Get the arg.
$arg = $data['arg'];

// Delete?
if(substr_count($arg, '-') == 1) {
    $split_arg = explode('-', $arg);
    $new_arg = $split_arg[0];
    $delete = true;
    $confirm = false;
} else if(substr_count($arg, '-') == 2) {
    $split_arg = explode('-', $arg);
    $new_arg = $split_arg[0];
    $delete = true;
    $confirm = true;
} else {
    $new_arg = $arg;
    $delete = false;
    $confirm = false;
}


// Get the id.
$id = $data['id'];

// ID = 0 ?
if($new_arg == 0 && $delete == false && $confirm == false) {
    // Get hidden gyms?
    if($id == 0) {
        $hidden = true;
    } else {
        $hidden = false;
    }

    // Get the keys.
    $keys = raid_edit_gym_keys($new_arg, false, 'gym_delete', true, $hidden);

    // Set keys.
    $msg = '<b>' . getTranslation('gym_delete') . SP . '—' . SP . getTranslation('select_gym_name') . '</b>';

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
        $nav_keys[] = universal_inner_key($nav_keys, '0', 'gym_letter', 'gym_delete', getTranslation('back'));
        $nav_keys[] = universal_inner_key($nav_keys, '0', 'exit', '0', getTranslation('abort'));
        $nav_keys = inline_key_array($nav_keys, 2);
        // Merge keys.
        $keys = array_merge($keys, $nav_keys);
    }

// Get gym info and ask to delete gym.
} else if ($new_arg > 0 && $delete == true && $confirm == false) {
    $gym = get_gym($new_arg);
    
    // Set message
    $msg = EMOJI_WARN . SP . '<b>' . getTranslation('delete_this_gym') . '</b>' . SP . EMOJI_WARN;
    $msg .= CR . get_gym_details($gym);

    // Create the keys.
    $keys = [
        [
            [
                'text'          => getTranslation('yes'),
                'callback_data' => $id . ':gym_delete:' . $new_arg . '-delete-yes'
            ]
        ],
        [
            [
                'text'          => getTranslation('no'),
                'callback_data' => '0:exit:0'
            ]
        ]
    ];

// Delete the gym.
} else if ($new_arg > 0 && $delete == true && $confirm == true) {
    debug_log('Deleting gym with ID ' . $new_arg);
    // Get gym.
    $gym = get_gym($new_arg);
    
    // Set message
    $msg = '<b>' . getTranslation('deleted_this_gym') . '</b>' . CR;
    $msg .= get_gym_details($gym);

    // Delete gym.
    delete_gym($new_arg);
    
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
