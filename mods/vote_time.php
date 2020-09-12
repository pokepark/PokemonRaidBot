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
$perform_share = true;
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
    // IF Chat was shared to target channel -> no new share
    if ($rs_chann->rowCount() > 0) {
        $perform_share = false;
    }
}


// Check if the user has voted for this raid before.
if($count_att > 0){
    $rs = my_query(
        "
        SELECT    user_id, remote, (1 + extra_valor + extra_instinct + extra_mystic) as user_count
        FROM      attendance
          WHERE   raid_id = {$data['id']}
            AND   user_id = {$update['callback_query']['from']['id']}
        "
    );

    // Get the answer.
    $answer = $rs->fetch();

    // Write to log.
    debug_log($answer);
}else{
    $answer = NULL;
}

// Get the arg.
$arg = $data['arg'];

// Raid anytime?
if($arg == 0) {
    // Raid anytime.
    $attend_time = ANYTIME;
} else {
    // Normal raid time - convert data arg to UTC time.
    $dt = new DateTime();
    $dt_attend = $dt->createFromFormat('YmdHis', $arg, new DateTimeZone('UTC'));
    $attend_time = $dt_attend->format('Y-m-d H:i:s');
}

// Get current time.
$now = new DateTime('now', new DateTimeZone('UTC'));
$now = $now->format('Y-m-d H:i') . ':00';

// Vote time in the future or Raid anytime?
if($now <= $attend_time || $arg == 0) {
    // If user is attending remotely, get the number of remote users already attending
    $remote_users = (($answer['remote']==0) ? 0 : get_remote_users_count($data['id'], $update['callback_query']['from']['id'], $attend_time));
    // Check if max remote users limit is already reached, unless voting for 'Anytime'
    if ($answer['remote'] == 0 || $remote_users + $answer['user_count'] <= $config->RAID_REMOTEPASS_USERS_LIMIT || $arg == 0) {
        // User has voted before.
        if (!empty($answer)) {
            // Update attendance.
            alarm($data['id'],$update['callback_query']['from']['id'],'change_time', $attend_time);
            my_query(
                "
                UPDATE    attendance
                SET       attend_time = '{$attend_time}',
                          cancel = 0,
                          arrived = 0,
                          raid_done = 0,
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
            // Share Raid to another Channel
            $chat = $config->SHARE_CHATS_AFTER_ATTENDANCE;
            // Request Raid and Gym - Infos
            $answer_gym = get_gym_raid($data['id']);
            // Set text.
            $text = show_raid_poll($answer_gym);
            // Set keys.
            $keys = keys_vote($answer_gym);
            // Set reply to.
            $reply_to = $chat;
            // Send the message.
            if($config->RAID_PICTURE) {
                require_once(LOGIC_PATH . '/raid_picture.php');
                $picture_url = raid_picture_url($data);
                $tg_json[] = send_photo($chat, $picture_url, $text['short'], $keys, ['reply_to_message_id' => $reply_to, 'reply_markup' => ['selective' => true, 'one_time_keyboard' => true], 'disable_web_page_preview' => 'true'], true);
            } else {
                $tg_json[] = send_message($chat, $text['full'], ['inline_keyboard' => $keys, 'selective' => true, 'one_time_keyboard' => true], ['disable_web_page_preview' => 'true'], true);
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

/**
 *  get all raid info - very detailed,
 *  because gyms->gym_id (ingress portal id) is different to
 *  raids->gym_id (the real one)
 * @param $raid_id
 * @return array
 */
function get_gym_raid($raid_id){
  $request_gym = my_query(
      "
      SELECT
          r.id AS id,
          r.user_id AS user_id,
          r.pokemon AS pokemon,
          r.pokemon_form AS pokemon_form,
          r.first_seen AS first_seen,
          r.start_time AS start_time,
          r.end_time AS end_time,
          r.gym_team AS gym_team,
          r.gym_id AS gym_id,
          r.move1 AS move1,
          r.move2 AS move2,
          r.gender AS gender,
          g.lon AS lon,
          g.lat AS lat,
          g.address AS address,
          g.gym_name AS gym_name,
          g.ex_gym AS ex_gym,
          g.show_gym AS show_gym,
          g.gym_note AS gym_note,
          g.gym_id AS gym_id2,
          g.img_url AS img_url
      FROM raids as r
      left join gyms as g on r.gym_id = g.id
      WHERE r.id = {$raid_id}
      "
  );
  $answer_gym = $request_gym->fetch();
  return $answer_gym;
}
