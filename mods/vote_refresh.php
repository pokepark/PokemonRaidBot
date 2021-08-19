<?php
require_once(LOGIC_PATH . '/update_raid_poll.php');

$tg_json = update_raid_poll($data['id'], false, $update, false, false, false, ($data['arg']=1?false:true));

$tg_json[] = answerCallbackQuery($update['callback_query']['id'], getTranslation('vote_updated'), true);

curl_json_multi_request($tg_json);

$dbh = null;
exit();
