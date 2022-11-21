<?php
// Write to log.
debug_log('vote_time()');
require_once(LOGIC_PATH . '/alarm.php');
require_once(LOGIC_PATH . '/get_remote_users_count.php');
require_once(LOGIC_PATH . '/sendalarmnotice.php');
require_once(LOGIC_PATH . '/send_vote_remote_users_limit_reached.php');
require_once(LOGIC_PATH . '/send_vote_time_future.php');

// For debug.
//debug_log($update);
//debug_log($data);

$raidId = $data['id'];

$vote_time = $data['arg'];
// Raid anytime?
$attend_time_save = $attend_time_compare = ANYTIME;
if($vote_time != 0) {
  // Normal raid time - convert data arg to UTC time.
  $dt = new DateTime();
  $dt_attend = $dt->createFromFormat('YmdHis', $vote_time, new DateTimeZone('UTC'));
  $attend_time_compare = $dt_attend->format('Y-m-d H:i:s');
  $attend_time_save = $dt_attend->format('Y-m-d H:i') . ':00';

  // Get current time.
  $now = new DateTime('now', new DateTimeZone('UTC'));
  $now = $now->format('Y-m-d H:i') . ':00';
}

// Vote time in the future?
if($attend_time_compare != ANYTIME && $now >= $attend_time_compare) {
  // Send vote time first.
  send_vote_time_future($update);
  exit;
}

// Request Raid and Gym - Infos
$raid = get_raid($raidId);

// Check if the user has voted for this raid before.
$rs = my_query('
  SELECT  count(attendance.attend_time) AS count, userInfo.*
  FROM    attendance
  LEFT JOIN (
    SELECT  raid_id, user_id, remote, attend_time, extra_alien,
            (CASE WHEN remote = 1 THEN 1 + extra_in_person ELSE 0 END + extra_alien) as user_count,
            CASE WHEN cancel = 1 or raid_done = 1 THEN 1 ELSE 0 END as cancelOrDone
    FROM    attendance
    WHERE   raid_id = :raidId
    AND     user_id = :userId
    LIMIT 1
    ) as userInfo
  ON    userInfo.raid_id = attendance.raid_id
  WHERE attendance.raid_id = :raidId
  ',
  [
    'raidId' => $raidId,
    'userId' => $update['callback_query']['from']['id'],
  ]
);

// Get the answer.
$answer = $rs->fetch();
// Get number of participants, if 0 -> raid has no participant at all
$count_att = $answer['count'] ?? 0;

// Write to log.
debug_log($count_att, 'Anyone Voted: ');
debug_log($answer);

$tg_json = [];

// User has voted before.
if ($answer['user_count'] != NULL) {
  // Exit if user is voting for the same time again unless they are done/canceled
  if(array_key_exists('attend_time', $answer) && $answer['cancelOrDone'] == 0 && $attend_time_save == $answer['attend_time']) {
    answerCallbackQuery($update['callback_query']['id'], 'OK');
    exit;
  }
  // If user voted something else than 'Anytime', get the number of remote users already attending
  $remote_users = ($vote_time == 0) ? 0 : get_remote_users_count($raidId, $update['callback_query']['from']['id'], $attend_time_save);

  // Check if max remote users limit is already reached, unless voting for 'Anytime'
  if(($answer['remote'] != 0 or $answer['extra_alien'] > 0) && $vote_time != 0 && $config->RAID_REMOTEPASS_USERS_LIMIT < ($remote_users + $answer['user_count'])) {
    // Send max remote users reached.
    send_vote_remote_users_limit_reached($update);
    exit;
  }

  // Update attendance.
  $update_pokemon_sql = '';
  if(!in_array($raid['pokemon'], $eggs)) {
    // If raid egg has hatched
    // -> clean up attendance table from votes for other pokemon
    // -> leave one entry remaining and set the pokemon to 0 there
    my_query('
    DELETE  a1
    FROM  attendance a1
    INNER JOIN attendance a2
    WHERE   a1.id < a2.id
      AND  a1.user_id = a2.user_id
      AND  a2.raid_id = :raid_id
      AND  a1.raid_id = :raid_id
    ',
    [
      'raid_id' => $raid['id'],
    ]);
    $update_pokemon_sql = 'pokemon = \'0\',';
  }
  my_query('
    UPDATE  attendance
    SET     attend_time = :attendTimeSave,
            cancel = 0,
            arrived = 0,
            raid_done = 0,
            ' . $update_pokemon_sql . '
            late = 0
    WHERE   raid_id = :raidId
    AND     user_id = :userId
    ',
    [
      'attendTimeSave' => $attend_time_save,
      'raidId' => $raidId,
      'userId' => $update['callback_query']['from']['id'],
    ]
  );
  $tg_json = alarm($raidId, $update['callback_query']['from']['id'], 'change_time', $attend_time_save, $tg_json);

// User has not voted before.
} else {
  $q_user = my_query("SELECT auto_alarm FROM users WHERE user_id ='" . $update['callback_query']['from']['id'] . "' LIMIT 1");
  $set_alarm = (!$config->RAID_AUTOMATIC_ALARM) ? $q_user->fetch()['auto_alarm'] : 1;

  // Create attendance.
  // Save attandence to DB + Set Auto-Alarm on/off according to config
  $insert_sql = my_query('
    INSERT INTO attendance SET
    raid_id     = :raidId,
    user_id     = :userId,
    attend_time = :attendTime,
    alarm       = :alarm
    ',
    [
      'raidId' => $raidId,
      'userId' => $update['callback_query']['from']['id'],
      'attendTime' => $attend_time_save,
      'alarm' => $set_alarm,
    ]);
  // Send Alarm.
  $tg_json = alarm($raidId,$update['callback_query']['from']['id'],'new_att', $attend_time_save, $tg_json);

  // Enable alerts message. -> only if alert is on
  if($set_alarm == 1) {
    // Inform User about active alert
    sendAlertOnOffNotice($raidId, $update['callback_query']['from']['id'], 1, $raid);
  }

  // Check if RAID has no participants AND Raid should be shared to another chat at first participant
  // AND target chat was set in config AND Raid was not shared to target chat before
  if($count_att == 0 && $config->SHARE_AFTER_ATTENDANCE && !empty($config->SHARE_CHATS_AFTER_ATTENDANCE)){
    // Send the message.
    require_once(LOGIC_PATH . '/send_raid_poll.php');
    $tg_json = send_raid_poll($raidId, $config->SHARE_CHATS_AFTER_ATTENDANCE, $raid, $tg_json);
  }
}

// Send vote response.
require_once(LOGIC_PATH . '/update_raid_poll.php');

$tg_json = update_raid_poll($raidId, $raid, $update, $tg_json);

$tg_json[] = answerCallbackQuery($update['callback_query']['id'], getTranslation('vote_updated'), true);

curl_json_multi_request($tg_json);
