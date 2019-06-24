<?php
// Write to log.
debug_log('importal()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
bot_access_check($update, 'portal-import');

// Ingressportalbot icon
$icon = iconv('UCS-4LE', 'UTF-8', pack('V', 0x1F4DC));
$coords = explode('&pll=',$update['message']['entities']['1']['url'])[1];
$latlon = explode(',', $coords);
$lat = $latlon[0];
$lon = $latlon[1];
// Ingressportalbot
if(strpos($update['message']['text'], $icon . 'Portal:') === 0) {
    // Get portal name.
    $portal = trim(str_replace($icon . 'Portal:', '', strtok($update['message']['text'], PHP_EOL)));
    // Get portal address.
    $address = explode(PHP_EOL, $update['message']['text'])[1];
    $address = trim(explode(':', $address, 2)[1]);
// PortalMapBot
} else if(substr_compare(strtok($update['message']['text'], PHP_EOL), '(Intel)', -strlen('(Intel)')) === 0) {
    // Get portal name.
    $portal = trim(substr(strtok($update['message']['text'], PHP_EOL), 0, -strlen('(Intel)')));
    // Get portal address.
    $address = trim(explode(PHP_EOL, $update['message']['text'])[4]);
}

// Remove country from address, e.g. ", Netherlands"
$address = explode(',',$address,-1);
$address = trim(implode(',',$address));

// Empty address? Try lookup.
if(empty($address)) {
    // Get address.
    $addr = get_address($lat, $lon);
    $address = format_address($addr);
}

// Write to log.
debug_log('Detected message from @PortalMapBot');
debug_log($portal, 'Portal:');
debug_log($coords, 'Coordinates:');
debug_log($lat, 'Latitude:');
debug_log($lon, 'Longitude:');
debug_log($address, 'Address:');

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

// Send the message.
send_message($update['message']['chat']['id'], $msg, $keys, ['disable_web_page_preview' => 'true']);

?>
