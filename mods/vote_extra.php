<?php
// Write to log.
debug_log('vote()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check if the user has voted for this raid before and check if they are attending remotely.
$rs = my_query(
    "
    SELECT    user_id, remote, want_invite, (1 + extra_in_person) as user_count
    FROM      attendance
      WHERE   raid_id = {$data['id']}
        AND   user_id = {$update['callback_query']['from']['id']}
    "
);

// Get the answer.
$answer = $rs->fetch();

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
            SET       extra_in_person = 0,
                    extra_alien = 0
            WHERE   raid_id = {$data['id']}
            AND   user_id = {$update['callback_query']['from']['id']}
            "
        );
        alarm($data['id'],$update['callback_query']['from']['id'],'extra_alone',$data['arg']);
    } else {
        // Check if max remote users limit is already reached!
        $remote_users = get_remote_users_count($data['id'], $update['callback_query']['from']['id']);
        // Skip remote user limit check if user is not attending remotely.
        // If they are, check if attend time already has max number of remote users.
        // Also prevent user from adding more than max number of remote players even if 'Anytime' is selected
        if ($answer['remote'] == 0 or ($remote_users < $config->RAID_REMOTEPASS_USERS_LIMIT && $answer['user_count'] < $config->RAID_REMOTEPASS_USERS_LIMIT)) {
            if($answer['want_invite'] == 1 && $data['arg'] == 'alien') {
                // Force invite beggars to user extra_in_person even if they vote +alien
                $data['arg'] = 'in_person';
            }
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
            alarm($data['id'],$update['callback_query']['from']['id'],'extra',$data['arg']);
        } else {
            // Send max remote users reached.
            send_vote_remote_users_limit_reached($update);
        }
    }

    // Send vote response.
    if($config->RAID_PICTURE) {
	    send_response_vote($update, $data,false,false);
    } else {
	    send_response_vote($update, $data);
    }
} else {
    // Send vote time first.
    send_vote_time_first($update);
}

exit();
