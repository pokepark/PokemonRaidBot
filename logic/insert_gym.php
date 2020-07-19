<?php
/**
 * Insert gym.
 * @param $gym_name
 * @param $latitude
 * @param $longitude
 * @param $address
 */
function insert_gym($name, $lat, $lon, $address)
{
    global $db;

    // Build query to check if gym is already in database or not
    $rs = my_query(
        "
        SELECT    COUNT(*)
        FROM      gyms
          WHERE   gym_name = '{$name}'
         "
        );

    $row = $rs->fetch_row();

    // Gym already in database or new
    if (empty($row['0'])) {
        // Build query for gyms table to add gym to database
        debug_log('Gym not found in database gym list! Adding gym "' . $name . '" to the database gym list.');
        $rs = my_query(
            "
            INSERT INTO   gyms
            SET           lat = '{$lat}',
                              lon = '{$lon}',
                              gym_name = '{$db->real_escape_string($name)}',
                              address = '{$db->real_escape_string($address)}'
            "
        );
    } else {
        // Update gyms table to reflect gym changes.
        debug_log('Gym found in database gym list! Updating gym "' . $name . '" now.');
        $rs = my_query(
            "
            UPDATE        gyms
            SET           lat = '{$lat}',
                              lon = '{$lon}',
                              address = '{$db->real_escape_string($address)}'
               WHERE      gym_name = '{$name}'
            "
        );
    }
}

?>
