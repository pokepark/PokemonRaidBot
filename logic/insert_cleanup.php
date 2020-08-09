<?php
/**
 * Insert raid cleanup info to database.
 * @param $chat_id
 * @param $message_id
 * @param $raid_id
 */
function insert_cleanup($chat_id, $message_id, $raid_id)
{
    // Log ID's of raid, chat and message
    debug_log('Raid_ID: ' . $raid_id);
    debug_log('Chat_ID: ' . $chat_id);
    debug_log('Message_ID: ' . $message_id);

    if ((is_numeric($chat_id)) && (is_numeric($message_id)) && (is_numeric($raid_id)) && ($raid_id > 0)) {
        global $db;

        // Get raid times.
        $raid = get_raid($raid_id);

	// Init found.
	$found = false;

        // Insert cleanup info to database
        if ($raid) {
	    // Check if cleanup info is already in database or not
	    // Needed since raids can be shared to multiple channels / supergroups!
	    $rs = my_query(
                "
		SELECT    *
            	    FROM      cleanup
                    WHERE     raid_id = '{$raid_id}'
                "
            );

	    // Chat_id and message_id equal to info from database
	    while ($cleanup = $rs->fetch()) {
		// Leave while loop if cleanup info is already in database
		if(($cleanup['chat_id'] == $chat_id) && ($cleanup['message_id'] == $message_id)) {
            	    debug_log('Cleanup preparation info is already in database!');
		    $found = true;
		    break;
		}
	    }
	}

	// Insert into database when raid found but no cleanup info found
        if ($raid && !$found) {
            // Build query for cleanup table to add cleanup info to database
            debug_log('Adding cleanup info to database:');
            $rs = my_query(
                "
                INSERT INTO   cleanup
                SET           raid_id = '{$raid_id}',
                              chat_id = '{$chat_id}',
                              message_id = '{$message_id}'
                "
            );
	}
    } else {
        debug_log('Invalid input for cleanup preparation!');
    }
}

?>
