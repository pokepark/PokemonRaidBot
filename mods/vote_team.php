<?php
// Write to log.
debug_log('vote_team()');

// For debug.
//debug_log($update);
//debug_log($data);

// Update team in users table.
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

// Send vote response.
send_response_vote($update, $data);

exit();
