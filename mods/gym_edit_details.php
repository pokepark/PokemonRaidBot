<?php
// Write to log.
debug_log('gym_edit_details()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
bot_access_check($update, 'gym-edit');

// Get the id.
$id = $data['id'];

// Get the arg.
$arg = $data['arg'];

// Split the arg.
$split_arg = explode('-', $arg);
$action = $split_arg[0];
$value = $split_arg[1];

// Set keys.
$keys = [];

// Update gym info.
if($action == 'show' || $action == 'ex') {
    $gym = get_gym($id);
    
    // Set message
    $msg = get_gym_details($gym, true);
    $msg .= CR . CR . '<b>' . getTranslation('new_extended_gym_detail') . '</b>';

    // New extended gym detail.
    if($action == 'show' && $value == 0) {
        $msg .= CR . '-' . SP . getTranslation('hide_gym');
    } else if($action == 'show' && $value == 1) {
        $msg .= CR . '-' . SP . getTranslation('show_gym');
    } else if($action == 'ex' && $value == 0) {
        $msg .= CR . '-' . SP . getTranslation('normal_gym');
    } else if($action == 'ex' && $value == 1) {
        $msg .= CR . '-' . SP . getTranslation('ex_gym');
    }
    $msg .= CR . CR . '<b>' . getTranslation('change_extended_gym_details') . '</b>';

    // Create the keys.
    $keys = [
        [
            [
                'text'          => getTranslation('yes'),
                'callback_data' => $id . ':gym_edit_details:' . 'confirm' . $action . '-' . $value
            ]
        ],
        [
            [
                'text'          => getTranslation('no'),
                'callback_data' => '0:exit:0'
            ]
        ]
    ];

} else if($action == 'confirmshow' || $action == 'confirmex') {
    debug_log('Changging the details for the gym with ID ' . $id);
    // Show or ex?
    $table = 'show_gym';
    if($action == 'confirmex') {
        $table = 'ex_gym';
    }

    my_query(
        "
        UPDATE    gyms
        SET       $table = $value
          WHERE   id = {$id}
        "
    );

    // Get gym.
    $gym = get_gym($id);
    
    // Set message.
    $msg = '<b>' . getTranslation('gym_saved') . '</b>';
    $msg .= CR . get_gym_details($gym, true);
}

// Build callback message string.
$callback_response = 'OK';

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
