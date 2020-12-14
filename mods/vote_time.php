<?php
// Write to log.
debug_log('vote_time()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check if someone has voted for this raid before.
$rs_count = my_query(
    "
    SELECT count(attend_time) AS count
    FROM attendance
      WHERE raid_id = {$data['id']}
    ");
// Get the answer.
$answer_count = $rs_count->fetch();
// Get number of participants, if 0 -> raid has no participant at all
$count_att = $answer_count['count'];
// Write to log.
debug_log($answer_count, 'Anyone Voted: ');
$perform_share = false; // no sharing by default
// Check if Raid has been posted to target channel
if($count_att == 0 && $config->SHARE_AFTER_ATTENDANCE && !empty($config->SHARE_CHATS_AFTER_ATTENDANCE)){
    $rs_chann = my_query(
        "
        SELECT *
        FROM cleanup
          WHERE raid_id = {$data['id']}
          AND chat_id = {$config->SHARE_CHATS_AFTER_ATTENDANCE}
          AND cleaned = 0
        ");
    // IF Chat was not shared to target channel we want to share it
    if ($rs_chann->rowCount() == 0) {
        $perform_share = true;
    }
}

// Request Raid and Gym - Infos
$raid = get_raid($data['id']);

// Check if the user has voted for this raid before.
if($count_att > 0){
    $rs = my_query(
        "
        SELECT    user_id, remote, (1 + extra_valor + extra_instinct + extra_mystic) as user_count
        FROM      attendance
          WHERE   raid_id = {$data['id']}
            AND   user_id = {$update['callback_query']['from']['id']}
            LIMIT 1
        "
    );

    // Get the answer.
    $answer = $rs->fetch();

    // Write to log.
    debug_log($answer);
}else{
    $answer = null;
}

$vote_time = $data['arg'];
// Raid anytime?
if($vote_time == 0) {
    // Raid anytime.
    $attend_time = ANYTIME;
} else {
    // Normal raid time - convert data arg to UTC time.
    $dt = new DateTime();
    $dt_attend = $dt->createFromFormat('YmdHis', $vote_time, new DateTimeZone('UTC'));
    $attend_time = $dt_attend->format('Y-m-d H:i:s');
}

// Get current time.
$now = new DateTime('now', new DateTimeZone('UTC'));
$now = $now->format('Y-m-d H:i') . ':00';

// Vote time in the future or Raid anytime?
if($now <= $attend_time || $vote_time == 0) {
    // If user is attending remotely, get the number of remote users already attending
    $remote_users = (($answer['remote']==0) ? 0 : get_remote_users_count($data['id'], $update['callback_query']['from']['id'], $attend_time));
    // Check if max remote users limit is already reached, unless voting for 'Anytime'
    if ($answer['remote'] == 0 || $remote_users + $answer['user_count'] <= $config->RAID_REMOTEPASS_USERS_LIMIT || $vote_time == 0) {
        // User has voted before.
        if (!empty($answer)) {
            // Update attendance.
            alarm($data['id'],$update['callback_query']['from']['id'],'change_time', $attend_time);
            $update_pokemon_sql = '';
            if(!in_array($raid['pokemon'], $eggs)) {
                // If raid egg has hatched
                // -> clean up attendance table from votes for other pokemon
                // -> leave one entry remaining and set the pokemon to 0 there
                my_query("
                    DELETE  a1
                    FROM    attendance a1,
                            attendance a2
                    WHERE   a1.id < a2.id
                       AND  a1.user_id = a2.user_id
                    ");
                $update_pokemon_sql = 'pokemon = \'0\',';
            }
            my_query(
                "
                UPDATE    attendance
                SET       attend_time = '{$attend_time}',
                          cancel = 0,
                          arrived = 0,
                          raid_done = 0,
                          {$update_pokemon_sql}
                          late = 0
                  WHERE   raid_id = {$data['id']}
                    AND   user_id = {$update['callback_query']['from']['id']}
                "
            );

        // User has not voted before.
        } else {
            // Create attendance.
            // Send Alarm.
            alarm($data['id'],$update['callback_query']['from']['id'],'new_att', $attend_time);
            // Save attandence to DB + Set Auto-Alarm on/off according to config
            $insert_sql="INSERT INTO attendance SET
              raid_id = :raid_id,
              user_id = :user_id,
              attend_time = :attend_time,
              alarm = :alarm";
            $dbh->prepare($insert_sql)->execute([
              'raid_id' => $data['id'],
              'user_id' => $update['callback_query']['from']['id'],
              'attend_time' => $attend_time,
              'alarm' => ($config->RAID_AUTOMATIC_ALARM ? 1 : 0)
            ]);

            // Enable alerts message. -> only if alert is on
            if($config->RAID_AUTOMATIC_ALARM) {
                // Inform User about active alert
                sendAlertOnOffNotice($data, $update, $config->RAID_AUTOMATIC_ALARM);
            }
        }
        // Check if RAID has no participants AND Raid should be shared to another Channel at first participant
        // AND target channel was set in config AND Raid was not shared to target channel before
        if($count_att == 0 && $config->SHARE_AFTER_ATTENDANCE && !empty($config->SHARE_CHATS_AFTER_ATTENDANCE) && $perform_share){
          // TODO(artanicus): This code is very WET, I'm sure we have functions somewhere to send a raid share -_-
            // Share Raid to another Channel
            $chat = $config->SHARE_CHATS_AFTER_ATTENDANCE;
            // Set text.
            $text = show_raid_poll($raid);
            // Set keys.
            $keys = keys_vote($raid);
            // Set reply to.
            $reply_to = $chat;
            // Send the message.
            if($config->RAID_PICTURE) {
                require_once(LOGIC_PATH . '/raid_picture.php');
                $picture_url = raid_picture_url($data);
                $tg_json[] = send_photo($chat, $picture_url, $text['short'], $keys, ['reply_to_message_id' => $reply_to, 'reply_markup' => ['selective' => true, 'one_time_keyboard' => true], 'disable_web_page_preview' => 'true'], true);
            } else {
                $tg_json[] = send_message($chat, $text['full'], $keys, ['reply_to_message_id' => $reply_to, 'reply_markup' => ['selective' => true, 'one_time_keyboard' => true], 'disable_web_page_preview' => 'true'], true);
            }
            // Telegram multicurl request.
            curl_json_multi_request($tg_json);
        }
    } else {
        // Send max remote users reached.
        send_vote_remote_users_limit_reached($update);
    }

} else {
    // Send vote time first.
    send_vote_time_future($update);
    send_response_vote($update, $data);
}

    // Send vote response.
   if($config->RAID_PICTURE) {
	    send_response_vote($update, $data,false,false);
    } else {
	    send_response_vote($update, $data);
    }

exit();