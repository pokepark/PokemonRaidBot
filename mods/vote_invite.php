<?php
// Write to log.
debug_log('vote_invite()');

// For debug.
//debug_log($update);
//debug_log($data);

// Update team in users table.
my_query('
  UPDATE attendance
  SET    invite = CASE
            WHEN invite = 0 THEN 1
            ELSE 0
          END
  WHERE  raid_id = ?
  AND    user_id = ?
  ', [$data['r'], $update['callback_query']['from']['id']]
);

// Send vote response.
require_once(LOGIC_PATH . '/update_raid_poll.php');

$tg_json = update_raid_poll($data['r'], false, $update);

$tg_json[] = answerCallbackQuery($update['callback_query']['id'], getTranslation('vote_updated'), true);

curl_json_multi_request($tg_json);

exit();
