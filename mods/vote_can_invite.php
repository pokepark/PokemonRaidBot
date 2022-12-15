<?php
// Write to log.
debug_log('vote_can_invite()');
require_once(LOGIC_PATH . '/alarm.php');
require_once(LOGIC_PATH . '/send_vote_time_first.php');

// For debug.
//debug_log($update);
//debug_log($data);

$raidId = $data['r'];

$query_select = my_query('
  SELECT  can_invite, CASE WHEN cancel = 1 or raid_done = 1 THEN 1 ELSE 0 END as cancelOrDone
  FROM  attendance
  WHERE   raid_id = :raid_id
  AND   user_id = :user_id
  LIMIT 1
  ',
  [
    'raid_id' => $raidId,
    'user_id' => $update['callback_query']['from']['id'],
  ]);
$res = $query_select->fetch();
if($query_select->rowCount() == 0 or $res['cancelOrDone'] == 1) {
  // Send vote time first.
  send_vote_time_first($update);
  exit;
}
my_query('
  UPDATE  attendance
  SET   can_invite = CASE
            WHEN can_invite = 0 THEN 1
            ELSE 0
            END,
        late = 0,
        arrived = 0,
        remote = 0,
        want_invite = 0,
        extra_alien = 0,
        extra_in_person = 0
  WHERE   raid_id = :raid_id
  AND     user_id = :user_id
  ',
  [
    'raid_id' => $raidId,
    'user_id' => $update['callback_query']['from']['id'],
  ]);
// Send vote response.
require_once(LOGIC_PATH . '/update_raid_poll.php');

$tg_json = update_raid_poll($raidId, false, $update);

$alarm_action = ($res['can_invite'] == 0) ? 'can_invite' : 'no_can_invite';
$tg_json = alarm($raidId,$update['callback_query']['from']['id'], $alarm_action, '', $tg_json);

$tg_json[] = answerCallbackQuery($update['callback_query']['id'], getTranslation('vote_updated'), true);

curl_json_multi_request($tg_json);
