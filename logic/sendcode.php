<?php
/**
 * Sending group code to the user.
 * @param $text
 * @param $raid
 * @param $user
 * @param $who
 */
function sendcode($text, $raid, $user, $who)
{
    // Will fetch all Trainer which attend the raid and send the message
    if($who == 'public') {
        $sql_remote = '';
    } else if($who == 'remote') {
        $sql_remote = 'AND remote = 1';
    } else if($who == 'local') {
        $sql_remote = 'AND remote = 0';
    }

    $request = my_query("SELECT DISTINCT user_id FROM attendance WHERE raid_id = {$raid} $sql_remote AND attend_time = (SELECT attend_time from attendance WHERE raid_id = {$raid} AND user_id = $user)");
    while($answer = $request->fetch_assoc())
    {
        // Only send message for other users!
        if($user != $answer['user_id']) {
            sendmessage($answer['user_id'], $text);
        }
    }
}

?>
