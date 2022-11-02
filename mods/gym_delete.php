<?php
// Write to log.
debug_log('gym_delete()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
$botUser->accessCheck($update, 'gym-delete');

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
    $msg = 'ERROR!';
    $keys = [];
}

if ($new_arg > 0 && $delete == true && $confirm == false) {
    $gym = get_gym($new_arg);
    
    // Set message
    $msg = EMOJI_WARN . SP . '<b>' . getTranslation('delete_this_gym') . '</b>' . SP . EMOJI_WARN;
    $msg .= CR . get_gym_details($gym);

    // Create the keys.
    $keys = [
        [
            [
                'text'          => getTranslation('yes'),
                'callback_data' => '0:gym_delete:' . $new_arg . '-delete-yes'
            ]
        ],
        [
            [
                'text'          => getTranslation('no'),
                'callback_data' => $new_arg . ':gym_edit_details:'
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
    $keys = [];

    // Delete gym.
    my_query(
        "
        DELETE FROM gyms
        WHERE     id = {$new_arg}
        "
    );
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
