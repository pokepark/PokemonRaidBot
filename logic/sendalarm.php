<?php
/**
 * Sending the alert to the user.
 * @param $text
 * @param $raid
 * @param $user
 */
function sendalarm($text, $raid, $user)
{
    // Will fetch all Trainer, which has subscribed for an alarm and send the message
    $request = my_query("SELECT DISTINCT user_id FROM attendance WHERE raid_id = {$raid} AND cancel = 0 AND raid_done = 0 AND alarm = 1");
    while($answer = $request->fetch())
    {
        // Only send message for other users!
        if($user != $answer['user_id']) {
            sendmessage($answer['user_id'], $text);
        }
    }

}

?>
