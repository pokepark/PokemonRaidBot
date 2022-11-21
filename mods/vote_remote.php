<?php
// Write to log.
debug_log('vote_remote()');
require_once(LOGIC_PATH . '/alarm.php');
require_once(LOGIC_PATH . '/get_remote_users_count.php');
require_once(LOGIC_PATH . '/send_vote_remote_users_limit_reached.php');
require_once(LOGIC_PATH . '/send_vote_time_first.php');

// For debug.
//debug_log($update);
//debug_log($data);

$raidId = $data['id'];

// Get current remote status of user
$rs = my_query('
  SELECT remote, (1 + extra_in_person + extra_alien) as user_count, CASE WHEN cancel = 1 or raid_done = 1 THEN 1 ELSE 0 END as cancelOrDone
  FROM   attendance
  WHERE  raid_id = :raidId
  AND   user_id = :userId
  ',
  [
    'raidId' => $raidId,
    'userId' => $update['callback_query']['from']['id'],
  ]
);

// Get remote value.
$remote = $rs->fetch();

if($rs->rowCount() == 0 or $remote['cancelOrDone'] == 1) {
  // Send vote time first.
  send_vote_time_first($update);
  exit;
}

$remote_status = $remote['remote'];

// Check if max remote users limit is already reached!
$remote_users = get_remote_users_count($raidId, $update['callback_query']['from']['id']);
// Ignore max users reached when switching from remote to local otherwise check if max users reached?
if($remote_status == 0 && $config->RAID_REMOTEPASS_USERS_LIMIT < $remote_users + $remote['user_count']) {
  // Send max remote users reached.
  send_vote_remote_users_limit_reached($update);
  exit;
}
// Update users table.
my_query('
  UPDATE  attendance
  SET  remote = CASE
    WHEN remote = 0 THEN 1
    ELSE 0
    END,
    want_invite = 0,
    can_invite = 0
  WHERE  raid_id = :raidId
  AND   user_id = :userId
  ',
  [
    'raidId' => $raidId,
    'userId' => $update['callback_query']['from']['id'],
  ]
);

$tg_json = alarm($raidId, $update['callback_query']['from']['id'], ($remote_status == 0 ? 'remote' : 'no_remote'));

// Send vote response.
require_once(LOGIC_PATH . '/update_raid_poll.php');

$tg_json = update_raid_poll($raidId, false, $update, $tg_json);

$tg_json[] = answerCallbackQuery($update['callback_query']['id'], getTranslation('vote_updated'), true);

curl_json_multi_request($tg_json);
