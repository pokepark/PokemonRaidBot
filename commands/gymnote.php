<?php
// Write to log.
debug_log('GYMNOTE()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
bot_access_check($update, 'gym-note');

// Get gym by name.
// Trim away everything before "/gymnote "
$id_info = $update['message']['text'];
$id_info = substr($id_info, 9);
$id_info = trim($id_info);

// Display keys to get gym ids.
if(empty($id_info)) {
    debug_log('Missing gym note!');
    // Set message.
    $msg = CR . '<b>' . getTranslation('gym_id_note_missing') . '</b>';
    $msg .= CR . CR . getTranslation('gym_note_instructions');
    $msg .= CR . getTranslation('gym_note_example');
    $msg .= CR . CR . getTranslation('gym_note_reset');
    $msg .= CR . getTranslation('gym_note_reset_example');
    $msg .= CR . CR . getTranslation('gym_get_id_details');

    // Set keys.
    $keys = [];
} else {
    // Set keys.
    $keys = [];

    // Get gym id.
    $split_id_info = explode(',', $id_info,2);
    $id = $split_id_info[0];
    $info = $split_id_info[1];
    $info = trim($info);

    // Make sure we have a valid gym id.
    $gym = false;
    if(is_numeric($id)) {
        $gym = get_gym($id);
    }

    // Update gym info.
    if($gym && !empty($info) && strtolower($info) == 'reset') {
        debug_log('Deleting gym note for gym with ID: ' . $id);
        my_query(
            "
            UPDATE    gyms
            SET       gym_note = NULL
              WHERE   id = {$id}
            "
        );

        // Set message.
        $msg = get_gym_details($gym);
        $msg .= CR . '<b>' . getTranslation('gym_note_deleted') . '</b>';
    } else if($gym && !empty($info)) {
        debug_log('Adding gym note for gym with ID: ' . $id);
        debug_log('Gym note: ' . $info);
        $stmt = $dbh->prepare(
            "
            UPDATE    gyms
            SET       gym_note = :info
              WHERE   id = :id
            "
        );
        $stmt->execute([
          'info' => $info,
          'id' => $id
        ]);

        // Set message.
        $msg = get_gym_details($gym);
        $msg .= CR . CR . '<b>' . getTranslation('gym_note_new') . '</b>' . CR . EMOJI_INFO . SP . $info;
        $msg .= CR . CR . '<b>' . getTranslation('gym_note_added') . '</b>';
    } else if($gym && empty($info)) {
        debug_log('Missing gym note!');
        // Set message.
        $msg .= CR . '<b>' . getTranslation('gym_id_note_missing') . '</b>';
        $msg .= CR . CR . getTranslation('gym_note_instructions');
        $msg .= CR . getTranslation('gym_note_example');
        $msg .= CR . CR . getTranslation('gym_note_reset');
        $msg .= CR . getTranslation('gym_note_reset_example');
        $msg .= CR . CR . getTranslation('gym_get_id_details');
    } else {
        // Set message.
        $msg .= getTranslation('invalid_input');
    }
}

// Send message.
send_message($update['message']['chat']['id'], $msg, ['inline_keyboard' => $keys, 'selective' => true, 'one_time_keyboard' => true], ['disable_web_page_preview' => 'true']);

?>
