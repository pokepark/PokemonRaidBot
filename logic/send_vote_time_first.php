<?php
/**
 * Send please vote for a time first.
 * @param $update
 */
function send_vote_time_first($update)
{
  // Set the message.
  $msg = getTranslation('vote_time_first');

  // Answer the callback.
  answerCallbackQuery($update['callback_query']['id'], $msg);

  exit();
}
