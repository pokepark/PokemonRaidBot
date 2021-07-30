<?php
/**
 * Sending the alert to the user.
 * @param $text
 * @param $raid_id
 * @param $user
 */
function sendalarm($text, $raid_id, $user)
{
    // Will fetch all Trainer, which has subscribed for an alarm and send the message
    $request = my_query("SELECT DISTINCT user_id FROM attendance WHERE raid_id = {$raid_id} AND cancel = 0 AND raid_done = 0 AND alarm = 1");
    $tg_json = [];
    while($answer = $request->fetch())
    {
        // Only send message for other users!
        if($user != $answer['user_id']) {
            $tg_json[] = sendMessage($answer['user_id'], $text, true);
        }
    }
    if(count($tg_json) > 0) curl_json_multi_request($tg_json);
}

?>
