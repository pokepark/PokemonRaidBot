<?php
require_once(LOGIC_PATH . '/insert_cleanup.php');
require_once(LOGIC_PATH . '/insert_overview.php');
require_once(LOGIC_PATH . '/insert_trainerinfo.php');

/**
 * Process response from telegram api.
 * @param array $response Decoded json response from Telegram
 * @param string $request Request sent to Telegram
 * @param array|int|string $identifier raid array from get_raid, raid id or [overview/trainer]
 * @return mixed
 */
function collectCleanup($response, $request, $identifier = false)
{
  if(
    $identifier == false or
    !isset($response['result']['chat']['type']) or
    !in_array($response['result']['chat']['type'], ['channel','group','supergroup'])
  ) {return $response;}

  // Set chat and message_id
  $chat_id = $response['result']['chat']['id'];
  $message_id = $response['result']['message_id'];
  debug_log('Return data: Chat id: '.$chat_id.', message_id: '.$message_id.', type: '.(is_array($identifier) ? print_r($identifier,true) : $identifier));
  if($identifier == 'trainer') {
    debug_log('Adding trainermessage info to database now!');
    insert_trainerinfo($chat_id, $message_id);
    return $response;
  }
  if ($identifier == 'overview') {
    debug_log('Adding overview info to database now!');
    $chat_title = $response['result']['chat']['title'];
    $chat_username = $response['result']['chat']['username'] ?? '';

    insert_overview($chat_id, $message_id, $chat_title, $chat_username);
    return $response;
  }

  if(isset($response['result']['text']) && !empty($response['result']['text'])) {
    $type = 'poll_text';
  } else if(isset($response['result']['caption']) && !empty($response['result']['caption'])) {
    $type = 'poll_photo';
  } else if(isset($response['result']['venue']) && !empty($response['result']['venue'])) {
    $type = 'poll_venue';
  }else if(isset($response['result']['photo']) && !isset($response['result']['caption'])) {
    $type = 'photo';
  }
  $save_id = $unique_id = false;
  if(isset($response['result']['photo'])) {
    $largest_size = 0;
    foreach($response['result']['photo'] as $photo) {
      if($photo['file_size'] < $largest_size) continue;
      $largest_size = $photo['file_size'];
      $save_id = $photo['file_id'];
      $unique_id = $photo['file_unique_id'];
    }
    $standalone_photo = (array_key_exists('standalone_photo', $identifier) && $identifier['standalone_photo'] === true) ? 1 : 0;
    my_query('
      REPLACE INTO photo_cache
      VALUES (:id, :unique_id, :pokedex_id, :form_id, :raid_id, :ended, :start_time, :end_time, :gym_id, :standalone)
    ',[
      ':id' => $save_id,
      ':unique_id' => $unique_id,
      ':pokedex_id' => $identifier['pokemon'],
      ':form_id' => $identifier['pokemon_form'],
      ':raid_id' => ($identifier['raid_ended'] ? 0 : $identifier['id']),  // No need to save raid id if raid has ended
      ':ended' => $identifier['raid_ended'],
      ':start_time' => ($identifier['raid_ended'] ? 'NULL' : $identifier['start_time']),
      ':end_time' => ($identifier['raid_ended'] ? 'NULL' : $identifier['end_time']),
      ':gym_id' => $identifier['gym_id'],
      ':standalone' => $standalone_photo,
      ]
    );
  }
  $raid_id = is_array($identifier) ? $identifier['id'] : $identifier;
  insert_cleanup($chat_id, $message_id, $raid_id, $type, $unique_id);
  // Return response.
  return $response;
}
