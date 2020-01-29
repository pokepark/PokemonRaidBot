<?php
// Write to log.
debug_log('vote_status()');

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

// Make sure user has voted before.
if (!empty($answer)) {
    // Get status to update
    $status = $data['arg'];
    alarm($data['id'],$update['callback_query']['from']['id'],'status',$status);
  // Update attendance.
	if($status == 'alarm')
	{ //update regarding alarm
		// add alarm-value +1
		my_query(
		"
		UPDATE    attendance
		SET    alarm = CASE
             WHEN alarm = '0' THEN '1'
             ELSE '0'
           END
		  WHERE   raid_id = {$data['id']}
			AND   user_id = {$update['callback_query']['from']['id']}
		"

		);

		// request gym name
		$request = my_query("SELECT * FROM raids as r left join gyms as g on r.gym_id = g.id WHERE r.id = {$data['id']}");
		$answer_quests = $request->fetch_assoc();
		$gymname = $answer_quests['gym_name'];

		// Get the new value
		$rs = my_query(
		"
		SELECT    alarm
		FROM      attendance
		  WHERE   raid_id = {$data['id']}
			AND   user_id = {$update['callback_query']['from']['id']}
		"
		);
		$answer = $rs->fetch_assoc();
		// If value is even
		if($answer['alarm'])
		{
			sendmessage($update['callback_query']['from']['id'], getTranslation('alert_updates_on').': <b>'.$gymname.'</b>');
		}
		else
		{
			// If value is uneven
			sendmessage($update['callback_query']['from']['id'], getTranslation('alert_no_updates').': <b>'.$gymname.'</b>');
		}
	}
	else
	{
		// All other status-updates are using the short query
		my_query(
			"
			UPDATE    attendance
			SET       arrived = 0,
					  raid_done = 0,
					  cancel = 0,
					  late = 0,
					  $status = 1
			  WHERE   raid_id = {$data['id']}
				AND   user_id = {$update['callback_query']['from']['id']}
			"
		);
	}


    // Send vote response.
   if(RAID_PICTURE == true) {
	    send_response_vote($update, $data,false,false);
    } else {
	    send_response_vote($update, $data);
    }
} else {
    // Send vote time first.
    send_vote_time_first($update);
}

exit();
