<?php

$raid_id = $data['id'];

my_query('UPDATE raids SET end_time = date_sub(UTC_TIMESTAMP(),interval 1 minute) WHERE id = \'' . $raid_id . '\'');

require_once(LOGIC_PATH . '/update_raid_poll.php');
$tg_json = update_raid_poll($raid_id, false, $update, false, false);

$tg_json[] = edit_message($update, getTranslation('raid_done'), [], false, true);
$tg_json[] = answerCallbackQuery($update['callback_query']['id'], getTranslation("remote_raid_marked_ended"), true);

curl_json_multi_request($tg_json);
?>
