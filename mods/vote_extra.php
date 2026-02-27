<?php
// Write to log.
debug_log('vote()');
require_once(LOGIC_PATH . '/alarm.php');
require_once(LOGIC_PATH . '/get_remote_users_count.php');
require_once(LOGIC_PATH . '/send_vote_remote_users_limit_reached.php');
require_once(LOGIC_PATH . '/send_vote_time_first.php');

// For debug.
//debug_log($update);
//debug_log($data);

$raidId = $data['r'];
$act = $data['a'] ?? 0;

// Check if the user has voted for this raid before and check if they are attending remotely.
$rs = my_query('
  SELECT  user_id, remote, want_invite, can_invite, attend_time, (CASE WHEN remote = 1 THEN 1 + extra_in_person ELSE 0 END + extra_alien) as user_count, CASE WHEN cancel = 1 or raid_done = 1 THEN 1 ELSE 0 END as cancelOrDone
  FROM    attendance
    WHERE   raid_id = :raidId
    AND   user_id = :userId
  LIMIT 1
  ',
  [
    'raidId' => $raidId,
    'userId' => $update['callback_query']['from']['id'],
  ]
);

// Get the answer.
$answer = $rs->fetch();

// Telegram JSON array.
$tg_json = array();

// Write to log.
debug_log($answer);

// User has not voted before.
if (empty($answer) or $answer['cancelOrDone'] == 1) {
  // Send vote time first.
  send_vote_time_first($update);
  exit;
}

if($act == '0') {
  // Reset team extra people.
  my_query('
    UPDATE  attendance
    SET     extra_in_person = 0,
            extra_alien = 0
    WHERE   raid_id = :raidId
    AND     user_id = :userId
    ',
    [
      'raidId' => $raidId,
      'userId' => $update['callback_query']['from']['id'],
    ]
  );
  $tg_json = alarm($raidId, $update['callback_query']['from']['id'], 'extra_alone', $act, $tg_json);
} else if($answer['can_invite'] == 1 ) {
  // People who are only inviting others can't add extras
  $msg = getTranslation('vote_status_not_allowed');

  // Answer the callback.
  answerCallbackQuery($update['callback_query']['id'], $msg);

  exit;
} else {
  // Check if max remote users limit is already reached!
  $remote_users = get_remote_users_count($raidId, $update['callback_query']['from']['id']);
  // Skip remote user limit check if user is not attending remotely.
  // If they are, check if attend time already has max number of remote users.
  if (($answer['remote'] == 1 or $act == 'alien') && ($remote_users >= $config->RAID_REMOTEPASS_USERS_LIMIT or $answer['user_count'] >= $config->RAID_REMOTEPASS_USERS_LIMIT)) {
    // Send max remote users reached.
    send_vote_remote_users_limit_reached($update);
    exit;
  }
  // Force invite beggars to user extra_in_person even if they vote +alien
  if($answer['want_invite'] == 1 && $act == 'alien') $act = 'in_person';

  if(!in_array($act, ['in_person', 'alien'])) {
    error_log('Invalid vote variable: ' . $act);
    exit;
  }
  $team = 'extra_' . $act;
  // Increase team extra people.
  my_query('
    UPDATE  attendance
    SET   ' . $team . ' = ' . $team . ' + 1
    WHERE   raid_id = :raidId
    AND   user_id = :userId
    ',
    [
      'raidId' => $raidId,
      'userId' => $update['callback_query']['from']['id'],
    ]
  );
  $tg_json = alarm($raidId, $update['callback_query']['from']['id'], 'extra', $act, $tg_json);
}

// Send vote response.
require_once(LOGIC_PATH . '/update_raid_poll.php');

$tg_json = update_raid_poll($raidId, false, $update, $tg_json);

$tg_json[] = answerCallbackQuery($update['callback_query']['id'], getTranslation('vote_updated'), true);

curl_json_multi_request($tg_json);
