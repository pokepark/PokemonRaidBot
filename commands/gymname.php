<?php
// Write to log.
debug_log('GYMNAME()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
bot_access_check($update, 'gym-name');

// Get gym by name.
// Trim away everything before "/gymname "
$id_info = $update['message']['text'];
$id_info = substr($id_info, 9);
$id_info = trim($id_info);

// Display keys to get gym ids.
if(empty($id_info)) {
    debug_log('Missing gym name!');
    // Set message.
    $msg = '<b>' . getTranslation('gym_id_name_missing') . '</b>';
    $msg .= CR . CR . getTranslation('gym_name_instructions');
    $msg .= CR . getTranslation('gym_name_example');
    $msg .= CR . CR . getTranslation('gym_get_id_details');

    // Set keys.
    $keys = [];
} else {
    // Set keys.
    $keys = [];

    // Init vars.
    $gym = false;
    $info = '';
    $id = 0;
    $tg_id = '#' . $update['message']['from']['id'];

    // Get gym id.
    if(substr_count($id_info, ',') >= 1) {
        $split_id_info = explode(',', $id_info,2);
        $id = $split_id_info[0];
        $info = $split_id_info[1];
        $info = trim($info);

        // Make sure we have a valid gym id.
        if(is_numeric($id)) {
            $gym = get_gym($id);
        }
    }

    // Maybe get gym by telegram id?
    if(!$gym) {
        $gym = get_gym_by_telegram_id($tg_id);
        // Get new id.
        if($gym) {
            $id = $gym['id'];
            $info = $id_info;
        }
    }

    // Update gym info.
    if($gym && !empty($info) && $id > 0) {
        debug_log('Changing name for gym with ID: ' . $id);
        debug_log('Gym name: ' . $info);
        $stmt = $dbh->prepare(
            "
            UPDATE    gyms
            SET       gym_name = :info
            WHERE     id = :id
            "
        );
        $stmt->execute([
          'info' => $info,
          'id' => $id
        ]);

        // Set message.
        $gym = get_gym($id);
        $msg = get_gym_details($gym);
        $msg .= CR . '<b>' . getTranslation('gym_name_updated') . '</b>';
    } else if($gym && empty($info)) {
        debug_log('Missing gym name!');
        // Set message.
        $msg .= CR . '<b>' . getTranslation('gym_id_name_missing') . '</b>';
        $msg .= CR . CR . getTranslation('gym_name_instructions');
        $msg .= CR . getTranslation('gym_name_example');
        $msg .= CR . CR . getTranslation('gym_get_id_details');
    } else {
        // Set message.
        $msg .= getTranslation('invalid_input');
    }
}

// Send message.
send_message($update['message']['chat']['id'], $msg, ['inline_keyboard' => $keys, 'selective' => true, 'one_time_keyboard' => true], ['disable_web_page_preview' => 'true']);

?>
