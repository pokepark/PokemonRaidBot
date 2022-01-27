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

// Request Raid and Gym - Infos
$raid = get_raid($data['id']);

// Check if the user has voted for this raid before.
if($count_att > 0){
    $rs = my_query(
        "
        SELECT    user_id, remote, (1 + extra_in_person + extra_alien) as user_count
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
    $answer = [];
}

$tg_json = [];

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
    if (!is_array($answer) or !in_array('remote', $answer) or $answer['remote'] == 0){
      $remote_users = 0;
    } else {
      $remote_users = get_remote_users_count($data['id'], $update['callback_query']['from']['id'], $attend_time);
    }
    // Check if max remote users limit is already reached, unless voting for 'Anytime'
    if ((!empty($answer) && ($answer['remote'] == 0 || $remote_users + $answer['user_count'])) <= $config->RAID_REMOTEPASS_USERS_LIMIT || $vote_time == 0) {
        // User has voted before.
        if (!empty($answer)) {
            // Update attendance.
            $update_pokemon_sql = '';
            if(!in_array($raid['pokemon'], $eggs)) {
                // If raid egg has hatched
                // -> clean up attendance table from votes for other pokemon
                // -> leave one entry remaining and set the pokemon to 0 there
                my_query("
                    DELETE  a1
                    FROM    attendance a1
                    INNER JOIN attendance a2
                    WHERE   a1.id < a2.id
                       AND  a1.user_id = a2.user_id
                       AND  a2.raid_id = {$raid['id']}
                       AND  a1.raid_id = {$raid['id']}
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
            $tg_json = alarm($data['id'],$update['callback_query']['from']['id'],'change_time', $attend_time, $tg_json);

        // User has not voted before.
        } else {
            $q_user = my_query("SELECT auto_alarm FROM users WHERE user_id ='" . $update['callback_query']['from']['id'] . "' LIMIT 1");
            $user_alarm = $q_user->fetch()['auto_alarm'];
            if($config->RAID_AUTOMATIC_ALARM) {
                $set_alarm = true;
            }else {
                $set_alarm = ($user_alarm == 1 ? true : false);
            }
            // Create attendance.
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
              'alarm' => ($set_alarm ? 1 : 0)
            ]);
            // Send Alarm.
            $tg_json = alarm($data['id'],$update['callback_query']['from']['id'],'new_att', $attend_time, $tg_json);

            // Enable alerts message. -> only if alert is on
            if($set_alarm) {
                // Inform User about active alert
                sendAlertOnOffNotice($data['id'], $update['callback_query']['from']['id'], 1, $raid);
            }
        }
        // Check if RAID has no participants AND Raid should be shared to another chat at first participant
        // AND target chat was set in config AND Raid was not shared to target chat before
        if($count_att == 0 && $config->SHARE_AFTER_ATTENDANCE && !empty($config->SHARE_CHATS_AFTER_ATTENDANCE)){
            // Check if Raid has been posted to target chat
            $rs_chann = my_query(
                "
                SELECT *
                FROM cleanup
                WHERE raid_id = {$data['id']}
                AND chat_id = {$config->SHARE_CHATS_AFTER_ATTENDANCE}
                ");
            // IF raid was not shared to target chat, we want to share it
            if ($rs_chann->rowCount() == 0) {
                // Send the message.
                require_once(LOGIC_PATH . '/send_raid_poll.php');
                $tg_json = send_raid_poll($data['id'], $config->SHARE_CHATS_AFTER_ATTENDANCE, $raid, $tg_json);
            }
        }
    } else {
        // Send max remote users reached.
        send_vote_remote_users_limit_reached($update);
        $dbh = null;
        exit();
    }

} else {
    // Send vote time first.
    send_vote_time_future($update);
}

// Send vote response.
require_once(LOGIC_PATH . '/update_raid_poll.php');

$tg_json = update_raid_poll($data['id'], $raid, $update, $tg_json);

$tg_json[] = answerCallbackQuery($update['callback_query']['id'], getTranslation('vote_updated'), true);

curl_json_multi_request($tg_json);

$dbh = null;
exit();