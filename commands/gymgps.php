<?php
// Write to log.
debug_log('GYMGPS()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
bot_access_check($update, 'gym-gps');

// Get gym by name.
// Trim away everything before "/gymgps "
$id_info = $update['message']['text'];
$id_info = substr($id_info, 8);
$id_info = trim($id_info);

// Display keys to get gym ids.
if(empty($id_info)) {
    debug_log('Missing gym coordinates!');
    // Set message.
    $msg = CR . '<b>' . getTranslation('gym_id_gps_missing') . '</b>';
    $msg .= CR . CR . getTranslation('gym_gps_instructions');
    $msg .= CR . getTranslation('gym_gps_example');

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

    // Count commas given in info.
    $count = substr_count($info, ",");

    // 1 comma as it should be?
    // E.g. 52.5145434,13.3501189
    if($count == 1) {
        $lat_lon = explode(',', $info);
        $lat = $lat_lon[0];
        $lon = $lat_lon[1];

    // Lat and lon with comma instead of dot?
    // E.g. 52,5145434,13,3501189
    } else if($count == 3) {
        $lat_lon = explode(',', $info);
        $lat = $lat_lon[0] . '.' . $lat_lon[1];
        $lon = $lat_lon[2] . '.' . $lat_lon[3];
    } else {
        // Invalid input - send the message and exit.
        $msg = '<b>' . getTranslation('invalid_input') . '</b>' . CR . CR;
        $msg .= getTranslation('gym_gps_coordinates_format_error') . CR;
        $msg .= getTranslation('gym_gps_example');
        sendMessage($update['message']['chat']['id'], $msg);
        exit();
    }

    // Make sure we have a valid gym id.
    $gym = false;
    if(is_numeric($id)) {
        $gym = get_gym($id);
    }

    if($gym && !empty($info)) {
        debug_log('Updating gps coordinates for gym with ID: ' . $id);
        debug_log('Gym latitude: ' . $lat);
        debug_log('Gym longitude: ' . $lon);
        my_query(
            "
            UPDATE    gyms
            SET       lat = {$lat},
                      lon = {$lon}
              WHERE   id = {$id}
            "
        );

        // Set message.
        $msg = get_gym_details($gym);
        $msg .= EMOJI_NEW . SP . $info;
        $msg .= CR . CR . '<b>' . getTranslation('gym_gps_added') . '</b>';
    } else if($gym && empty($info)) {
        debug_log('Missing gym coordinates!');
        // Set message.
        $msg .= CR . '<b>' . getTranslation('gym_id_gps_missing') . '</b>';
        $msg .= CR . CR . getTranslation('gym_gps_instructions');
        $msg .= CR . getTranslation('gym_gps_example');
    } else {
        // Set message.
        $msg .= getTranslation('invalid_input');
    }
}

// Send message.
send_message($update['message']['chat']['id'], $msg, ['inline_keyboard' => $keys, 'selective' => true, 'one_time_keyboard' => true], ['disable_web_page_preview' => 'true']);

?>
