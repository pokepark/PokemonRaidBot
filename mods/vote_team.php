<?php
// Write to log.
debug_log('vote_team()');

// For debug.
//debug_log($update);
//debug_log($data);

// Update team in users table directly.
if($data['arg'] == ('mystic' || 'valor' || 'instinct')) {
    my_query(
        "
        UPDATE  users
        SET     team = '{$data['arg']}'
        WHERE   user_id = {$update['callback_query']['from']['id']}
        "
    );
// No team was given - iterate thru the teams.
} else {
    my_query(
        "
        UPDATE    users
        SET    team = CASE
                 WHEN team = 'mystic' THEN 'valor'
                 WHEN team = 'valor' THEN 'instinct'
                 ELSE 'mystic'
               END
          WHERE   user_id = {$update['callback_query']['from']['id']}
        "
    );
}

// Message coming from raid or trainer info?
if($data['id'] == 'trainer') {
    // Send trainer info update.
    send_trainerinfo($update, true);
} else {
    // Send vote response.
   if($config->RAID_PICTURE) {
	    send_response_vote($update, $data,false,false);
    } else {
	    send_response_vote($update, $data);
    } 
}

exit();
