<?php
// Write to log.
debug_log('GYM()');

// For debug.
//debug_log($update);
//debug_log($data);

// Get gym name.
$gym_name = trim(substr($update['message']['text'], 4));

// Write to log.
debug_log('Setting gym name to ' . $gym_name);

// Private chat type.
if ($update['message']['chat']['type'] == 'private') {

    try {
     
         // Update gym name in raid table.
        $query = '
            UPDATE gyms
            SET gym_name = :gym_name, show_gym = 1
            WHERE
                gym_id = :gym_id
            ORDER BY
                id DESC
            LIMIT 1
        ';
        $statement = $dbh->prepare( $query );
        $statement->bindValue(':gym_name', $gym_name, PDO::PARAM_STR);
        $statement->bindValue(':gym_id', $update['message']['from']['id'], PDO::PARAM_INT);
        $statement->execute();   
    }
    catch (PDOException $exception) {

        error_log($exception->getMessage());
        $dbh = null;
        exit;
    }

    // Send the message.
    sendMessage($update['message']['chat']['id'], getTranslation('gym_name_updated'));
}
?>
