<?php
// Write to log.
debug_log('vote_level()');

// For debug.
//debug_log($update);
//debug_log($data);

// Get action.
$action = $data['arg'];

// Up-vote.
if ($action == 'up') {
    // Increase users level.
    my_query(
        "
        UPDATE    users
        SET       level = IF(level = 0, 30, level+1)
          WHERE   user_id = {$update['callback_query']['from']['id']}
            AND   level < 40
        "
    );
}

// Down-vote.
if ($action == 'down') {
    // Decrease users level.
    my_query(
        "
        UPDATE    users
        SET       level = level-1
          WHERE   user_id = {$update['callback_query']['from']['id']}
            AND   level > 5
        "
    );
}

// Message coming from raid or trainer info?
if($data['id'] == 'trainer') {
    if($action == 'hide') {
        // Send trainer info update.
        send_trainerinfo($update, false);
    } else if($action == 'show') {
        // Send trainer info update.
        send_trainerinfo($update, true);
    } else {
        // Send trainer info update.
        send_trainerinfo($update, true);
    }
} else {
    // Send vote response.
   if($config->RAID_PICTURE) {
	    send_response_vote($update, $data,false,false);
    } else {
	    send_response_vote($update, $data);
    } 
}

exit();
