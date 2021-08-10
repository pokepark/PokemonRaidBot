<?php

$raid_id = $data['id'];

my_query('UPDATE raids SET end_time = date_sub(UTC_TIMESTAMP(),interval 1 minute) WHERE id = \'' . $raid_id . '\'');

if($config->RAID_PICTURE) {
    // This needs to be defined for send_response_vote to handle these hacked calls
    $update['callback_query']['message']['caption'] = true;
}

edit_message($update, getTranslation('raid_done'), []);
answerCallbackQuery($update['callback_query']['id'], getTranslation("remote_raid_marked_ended"));

// Send vote response.
if($config->RAID_PICTURE) {
   send_response_vote($update, $data,false,false);
} else {
   send_response_vote($update, $data);
}
?>