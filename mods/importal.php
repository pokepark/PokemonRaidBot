<?php
// Write to log.
debug_log('importal()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
bot_access_check($update, 'portal-import');

function escape($value){

    $search = array("\\",  "\x00", "\n",  "\r",  "'",  '"', "\x1a");
    $replace = array("\\\\","\\0","\\n", "\\r", "\'", '\"', "\\Z");

    return str_replace($search, $replace, $value);
}

// Import allowed?
if($config->PORTAL_IMPORT) {

    // Process message for portal information.
    require_once(CORE_BOT_PATH . '/importal.php');

    // Insert gym.
    try {

        global $dbh;

        // Gym name.
        $gym_name = $portal;
        if(empty($portal)) {
            $gym_name = '#' . $update['message']['from']['id'];
        }

        // Gym image.
        if($config->RAID_PICTURE_STORE_GYM_IMAGES_LOCALLY) {
			$no_spaces_gym_name = str_replace(array(' ', '\''), array('_', ''), $gym_name) . '.png';
            $gym_image = download_Portal_Image($portal_image, PORTAL_IMAGES_PATH, $no_spaces_gym_name);
            if($gym_image) {
                $gym_image = "file://" . $gym_image;
            }
        } else {
            $gym_image = $portal_image;
        }
		
		$gym_name_no_spec = escape($portal); // Convert special characters in gym name
        // Build query to check if gym is already in database or not
        // TODO: Use PDO here
        $rs = my_query("
        SELECT    id
        FROM      gyms
        WHERE   gym_name = '{$gym_name_no_spec}'
        ");

        $row = $rs->fetch();

        // Gym already in database or new
        if (empty($row['id'])) {
            // insert gym in table.
            debug_log('Gym not found in database gym list! Inserting gym "' . $gym_name . '" now.');
            $query = '
            INSERT INTO gyms (gym_name, lat, lon, address, show_gym, img_url)
            VALUES (:gym_name, :lat, :lon, :address, 0, :gym_image)
            ';
            $msg = getTranslation('gym_added');

        } else {
            // Update gyms table to reflect gym changes.
            debug_log('Gym found in database gym list! Updating gym "' . $gym_name . '" now.');
            $query = '
                UPDATE        gyms
                SET           lat = :lat,
                              lon = :lon,
                              address = :address,
                              img_url = :gym_image
                WHERE      gym_name = :gym_name
                ';
            $msg = getTranslation('gym_updated');
            $gym_id = get_gym_by_telegram_id(escape($gym_name));
            $gym_id = $gym_id['id'];
        }

        // Insert / Update.
        $statement = $dbh->prepare($query);
        $statement->execute([
          'gym_name' => $gym_name,
          'lat' => $lat,
          'lon' => $lon,
          'address' => $address,
          'gym_image' => $gym_image
        ]);
    } catch (PDOException $exception) {
        error_log($exception->getMessage());
        $dbh = null;
        exit();
    }

    // Get last insert id.
    if (empty($row['id'])) {
        $gym_id = $dbh->lastInsertId();
    }

    // Gym details.
    if($gym_id > 0) {
        $gym = get_gym($gym_id);
        $msg .= CR . CR . get_gym_details($gym);
    }

    // Gym photo.
    if($gym_image) {
        $msg .= EMOJI_CAMERA . SP . $no_spaces_gym_name;
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
