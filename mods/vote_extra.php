<?php
// Write to log.
debug_log('vote()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check if the user has voted for this raid before.
$rs = my_query(
    "
    SELECT    user_id
    FROM      attendance
      WHERE   raid_id = {$data['id']}
        AND   user_id = {$update['callback_query']['from']['id']}
    "
);

// Get the answer.
$answer = $rs->fetch_assoc();

// Write to log.
debug_log($answer);

// User has voted before.
if (!empty($answer)) {
    if($data['arg'] == '0')
    {
        // Reset team extra people.
        my_query(
            "
            UPDATE    attendance
            SET       extra_mystic = 0,
                      extra_valor = 0,
                      extra_instinct = 0
              WHERE   raid_id = {$data['id']}
                AND   user_id = {$update['callback_query']['from']['id']}
            "
        );
    } else {
        // Get team.
        $team = 'extra_' . $data['arg'];

        // Increase team extra people.
        my_query(
            "
            UPDATE    attendance
            SET       {$team} = {$team}+1
              WHERE   raid_id = {$data['id']}
                AND   user_id = {$update['callback_query']['from']['id']}
                AND   {$team} < 5
            "
        );
    }

    // Send vote response.
    send_response_vote($update, $data);
} else {
    // Send vote time first.
    send_vote_time_first($update);
}

exit();
