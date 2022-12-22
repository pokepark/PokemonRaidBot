<?php
require_once(LOGIC_PATH . '/update_raid_poll.php');

$tg_json = update_raid_poll($data['r'], false, $update, false);

$tg_json[] = answerCallbackQuery($update['callback_query']['id'], getTranslation('vote_updated'), true);

curl_json_multi_request($tg_json);

exit();
