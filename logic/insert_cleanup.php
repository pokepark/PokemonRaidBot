<?php
/**
 * Insert raid cleanup info to database.
 * @param int $chat_id
 * @param int $message_id
 * @param int $thread_id
 * @param int $raid_id
 * @param string $type
 * @param string|null $photo_id
 */
function insert_cleanup($chat_id, $message_id, $thread_id, $raid_id, $type, $photo_id = NULL)
{
  // Log ID's of raid, chat and message
  debug_log('Raid_ID: ' . $raid_id);
  debug_log('Chat_ID: ' . $chat_id);
  debug_log('Message_ID: ' . $message_id);
  debug_log('Thread_ID: ' . $thread_id);
  debug_log('Type: ' . $type);

  if (!is_numeric($chat_id) || !is_numeric($message_id) || !is_numeric($raid_id) || $raid_id < 1) {
    debug_log('Invalid input for cleanup preparation!');
    return;
  }

  // Build query for cleanup table to add cleanup info to database
  debug_log('Adding cleanup info to database:');
  my_query('
    REPLACE INTO  cleanup
    SET     raid_id = :raid_id,
            chat_id = :chat_id,
            message_id = :message_id,
            thread_id = :thread_id,
            type = :type,
            media_unique_id = :media_unique_id
    ', [
      ':raid_id' => $raid_id,
      ':chat_id' => $chat_id,
      ':message_id' => $message_id,
      ':thread_id' => $thread_id,
      ':type' => $type,
      ':media_unique_id' => $photo_id,
    ]
  );
}
