<?php
/**
 * Insert trainer info.
 * @param int $chat_id
 * @param int $message_id
 * @param int|null $thread_id
 */
function insert_trainerinfo($chat_id, $message_id, $thread_id)
{
  // Build query to check if trainer info details are already in database or not
  $rs = my_query('
    SELECT  COUNT(*) AS count
    FROM    trainerinfo
    WHERE   chat_id = ?
    ', [$chat_id]
    );

  $row = $rs->fetch();

  // Trainer info already in database or new
  if (!empty($row['count'])) {
    // Nothing to do - trainer information is already in database.
    debug_log('Trainer information is already in database! Nothing to do...');
    return;
  }
  // Build query for trainerinfo table to add trainer info to database
  debug_log('Adding new trainer information to database trainer info list!');
  my_query('
    INSERT INTO trainerinfo
    SET         chat_id = ?,
                message_id = ?,
                thread_id = ?
    ', [$chat_id, $message_id, $thread_id]
  );
}
