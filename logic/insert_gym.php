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
    global $dbh;

    // Build query to check if gym is already in database or not
    $stmt = $dbh->prepare("
        SELECT    COUNT(*) AS count
        FROM      gyms
        WHERE   gym_name = :name");

    $row = $stmt->fetch();

    // Gym already in database or new
    if (empty($row['count'])) {
        // Build query for gyms table to add gym to database
        debug_log('Gym not found in database gym list! Adding gym "' . $name . '" to the database gym list.');
        $stmt = $dbh->prepare("
            INSERT INTO   gyms
            SET           lat = :lat,
                          lon = :lon,
                          gym_name = :name,
                          address = :address
        ");
        $stmt.bindParam(':lat', $lat);
        $stmt.bindParam(':lon', $lon);
        $stmt.bindParam(':name', $name);
        $stmt.bindParam(':address', $address);
        $stmt->execute();
    } else {
      // Update gyms table to reflect gym changes.
      // TODO(@Artanicus): using gym name as the selector is bad and doesn't allow updating gym name
        debug_log('Gym found in database gym list! Updating gym "' . $name . '" now.');
        $stmt = $dbh->prepare("
            UPDATE        gyms
            SET           lat = :lat,
                          lon = :lon,
                          gym_name = :name,
                          address = :address
               WHERE      gym_name = :name
        ");
        $stmt.bindParam(':lat', $lat);
        $stmt.bindParam(':lon', $lon);
        $stmt.bindParam(':name', $name);
        $stmt.bindParam(':address', $address);
        $stmt->execute();
    }
}

?>
