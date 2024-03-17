<?php
// Write to log.
debug_log('vote_team()');
require_once(LOGIC_PATH . '/send_trainerinfo.php');

// For debug.
//debug_log($update);
//debug_log($data);
$team = $data['t'] ?? '';
$source = $data['a'] ?? '';

// Update team in users table directly.
if($team == ('mystic' || 'valor' || 'instinct')) {
  my_query('
    UPDATE  users
    SET   team = ?
    WHERE   user_id = ?
    ', [$team, $update['callback_query']['from']['id']]
  );
// No team was given - iterate thru the teams.
} else {
  my_query('
    UPDATE  users
    SET  team = CASE
         WHEN team = \'mystic\' THEN \'valor\'
         WHEN team = \'valor\' THEN \'instinct\'
         ELSE \'mystic\'
         END
      WHERE   user_id = ?
    ', [$update['callback_query']['from']['id']]
  );
}

// Message coming from raid or trainer info?
if($source == 'trainer') {
  // Send trainer info update.
  send_trainerinfo($update, true);
  exit;
}
// Send vote response.
require_once(LOGIC_PATH . '/update_raid_poll.php');

$tg_json = update_raid_poll($data['r'], false, $update);

$tg_json[] = answerCallbackQuery($update['callback_query']['id'], getTranslation('vote_updated'), true);

curl_json_multi_request($tg_json);
