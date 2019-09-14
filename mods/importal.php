<?php
// Write to log.
debug_log('importal()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
bot_access_check($update, 'portal-import');

// Import allowed?
if(defined('PORTAL_IMPORT') && PORTAL_IMPORT == true) {

    // Process message for portal information.
    require_once(CORE_BOT_PATH . '/importal.php');

    // Insert gym.
    try {

        global $db;

        // Gym name.
        $gym_name = $portal;
        if(empty($portal)) {
            $gym_name = '#' . $update['message']['from']['id'];
        }

        // Build query to check if gym is already in database or not
        $rs = my_query("
        SELECT    id, COUNT(*)
        FROM      gyms
          WHERE   gym_name = '{$gym_name}'
         ");

        $row = $rs->fetch_row();

        // Gym already in database or new
        if (empty($row['0'])) {
            // insert gym in table.
            debug_log('Gym not found in database gym list! Inserting gym "' . $gym_name . '" now.');
            $query = '
            INSERT INTO gyms (gym_name, lat, lon, address, show_gym)
            VALUES (:gym_name, :lat, :lon, :address, 0)
            ';
            $msg = getTranslation('gym_added');

        } else {
            // Update gyms table to reflect gym changes.
            debug_log('Gym found in database gym list! Updating gym "' . $gym_name . '" now.');
            $query = '
                UPDATE        gyms
                SET           lat = :lat,
                              lon = :lon,
                              address = :address
                WHERE      gym_name = :gym_name
                ';
            $msg = getTranslation('gym_updated');
            $gym_id = get_gym_by_telegram_id($gym_name);
            $gym_id = $gym_id['id'];
        }

        // Insert / Update.
        $statement = $dbh->prepare($query);
        $statement->bindValue(':gym_name', $gym_name, PDO::PARAM_STR);
        $statement->bindValue(':lat', $lat, PDO::PARAM_STR);
        $statement->bindValue(':lon', $lon, PDO::PARAM_STR);
        $statement->bindValue(':address', $address, PDO::PARAM_STR);
        $statement->execute();
    } catch (PDOException $exception) {
        error_log($exception->getMessage());
        $dbh = null;
        exit();
    }

    // Get last insert id.
    if (empty($row['0'])) {
        $gym_id = $dbh->lastInsertId();
    }

    // Gym details.
    if($gym_id > 0) {
        $gym = get_gym($gym_id);
        $msg .= CR . CR . get_gym_details($gym);
    }

    // Set keys.
    $keys = [
        [
            [
                'text'          => getTranslation('delete'),
                'callback_data' => $gym_name[0] . ':gym_delete:' . $gym_id . '-delete'
            ],
            [
                'text'          => getTranslation('show_gym'),
                'callback_data' => $gym_id . ':gym_edit_details:show-1'
            ]
        ],
        [
            [
                'text'          => getTranslation('done'),
                'callback_data' => '0:exit:1'
            ]
        ]
    ];
} else {
    $msg = getTranslation('bot_access_denied');
    $keys = [];
}

// Send the message.
send_message($update['message']['chat']['id'], $msg, $keys, ['disable_web_page_preview' => 'true']);

?>
