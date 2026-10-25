<?php
/**
 * Send vote for a future time.
 * @param $update
 */
function send_vote_time_future($update)
{
  // Set the message.
  $msg = getPublicTranslation('vote_time_future');

  // Answer the callback.
  answerCallbackQuery($update['callback_query']['id'], $msg);
}
