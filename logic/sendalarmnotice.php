<?php

/**
 * Sends notification to user if Alarm is on or off
 * @param int $raid_id
 * @param array $update
 * @param boolean $alarm 
 * @param string $gymname
 * @param string $raidtimes 
 */
function sendAlertOnOffNotice($data, $update, $alarm = null, $gymname = null, $raidtimes = null){
    
    if(empty($gymname)){
        // request gym name
        $request = my_query("SELECT * FROM raids as r left join gyms as g on r.gym_id = g.id WHERE r.id = {$data['id']}");
        $answer = $request->fetch();
        $gymname = '<b>' . $answer['gym_name'] . '</b>';
        if(empty($raidtimes)){
            // parse raidtimes
            $raidtimes = str_replace(CR, '', str_replace(' ', '', get_raid_times($answer, false, true)));
        }
    }

    if(empty($raidtimes)){
        //request raidtimes
        $request = my_query("SELECT * FROM raids as r left join gyms as g on r.gym_id = g.id WHERE r.id = {$data['id']}");
        $answer = $request->fetch();
        $raidtimes = str_replace(CR, '', str_replace(' ', '', get_raid_times($answer, false, true)));
    }

    if(empty($alarm)){
        // Get the new value
        $rs = my_query(
            "
            SELECT    alarm
            FROM      attendance
            WHERE     raid_id = {$data['id']}
            AND       user_id = {$update['callback_query']['from']['id']}
            "
            );
        $answer = $rs->fetch();
        $alarm = $answer['alarm'];
    }

    $msg_text = '';

    if($alarm) {// Enable alerts message.
        $msg_text = EMOJI_ALARM . SP . '<b>' . getTranslation('alert_updates_on') . '</b>' . CR;
    } else {// Disable alerts message.
        $msg_text = EMOJI_NO_ALARM . SP . '<b>' . getTranslation('alert_no_updates') . '</b>' . CR;
	}
    $msg_text .= EMOJI_HERE . SP . $gymname . SP . '(' . $raidtimes . ')';
    sendmessage($update['callback_query']['from']['id'], $msg_text);
}

?>
