<?php
/**
 * Insert overview.
 * @param $chat_id
 * @param $message_id
 * @param $chat_title
 * @param $chat_username
 */
function insert_overview($chat_id, $message_id, $chat_title, $chat_username)
{
  // Build query to check if overview details are already in database or not
  $rs = my_query('
    SELECT  COUNT(*) AS count
    FROM    overview
    WHERE   chat_id = ?
    ', [$chat_id]
    );

  $row = $rs->fetch();

  // Overview already in database or new
  if (!empty($row['count'])) {
    // Nothing to do - overview information is already in database.
    debug_log('Overview information is already in database! Nothing to do...');
    return;
  }
  // Build query for overview table to add overview info to database
  debug_log('Adding new overview information to database overview list!');
  my_query(
    '
    INSERT INTO overview
    SET   chat_id = :chat_id,
          message_id = :message_id,
          chat_title = :chat_title,
          chat_username = :chat_username,
          updated = DATE(NOW())
    ', [
      'chat_id' => $chat_id,
      'message_id' => $message_id,
      'chat_title' => $chat_title,
      'chat_username' => $chat_username,
    ]
  );
}
