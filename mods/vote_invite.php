<?php
// Write to log.
debug_log('vote_invite()');

// For debug.
//debug_log($update);
//debug_log($data);

// Update team in users table.
my_query(
    "
    UPDATE    attendance
    SET    invite = CASE
             WHEN invite = '0' THEN '1'
             ELSE '0'
           END
    WHERE   raid_id = {$data['id']}
    AND   user_id = {$update['callback_query']['from']['id']}
    "
);

// Send vote response.
   if($config->RAID_PICTURE) {
	    send_response_vote($update, $data,false,false);
    } else {
	    send_response_vote($update, $data);
    } 

exit();
