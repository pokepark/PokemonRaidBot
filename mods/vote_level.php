<?php
// Write to log.
debug_log('vote_level()');
require_once(LOGIC_PATH . '/send_trainerinfo.php');

// For debug.
//debug_log($update);
//debug_log($data);

// Get action.
$action = $data['arg'];

// Up-vote.
if ($action == 'up') {
  // Increase users level.
  my_query(
    '
    UPDATE  users
    SET     level = IF(level = 0, 30, level+1)
      WHERE   user_id = ?
      AND   level < ' . $config->TRAINER_MAX_LEVEL . '
    ', [$update['callback_query']['from']['id']]
  );
}

// Down-vote.
if ($action == 'down') {
  // Decrease users level.
  my_query(
    '
    UPDATE  users
    SET     level = level-1
      WHERE   user_id = ?
      AND   level > 5
    ', [$update['callback_query']['from']['id']]
  );
}

// Message coming from raid or trainer info?
if($data['id'] == 'trainer') {
  if($action == 'hide') {
    // Send trainer info update.
    send_trainerinfo($update, false);
  }
  // Send trainer info update.
  send_trainerinfo($update, true);
}
// Send vote response.
require_once(LOGIC_PATH . '/update_raid_poll.php');

$tg_json = update_raid_poll($data['id'], false, $update);

$tg_json[] = answerCallbackQuery($update['callback_query']['id'], getTranslation('vote_updated'), true);

curl_json_multi_request($tg_json);


exit();
