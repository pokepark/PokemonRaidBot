<?php
/**
 * Send remote pass user limit reached.
 * @param $update
 */
function send_vote_remote_users_limit_reached($update)
{
    // Set the message.
    $msg = getPublicTranslation('vote_remote_users_limit_reached');

    // Answer the callback.
    answerCallbackQuery($update['callback_query']['id'], $msg);
}

?>
