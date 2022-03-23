<?php

if ($metrics){
  $tg_response_code = $metrics->registerCounter($namespace, 'tg_response_count', 'Counters of response codes from Telegram', ['code', 'method', 'description']);
}

/**
 * Process response from telegram api.
 * @param string $json_response Json response from Telegram
 * @param string $json Json sent to Telegram
 * @param array|int|string $identifier raid array from get_raid, raid id or [overview/trainer]
 * @return mixed
 */
function curl_json_response($json_response, $json, $identifier = false)
{
    global $config, $metrics, $tg_response_code;
    // Write to log.
    debug_log_incoming($json_response, '<-');

    // Decode json objects
    $request = json_decode($json, true);
    $response = json_decode($json_response, true);
    if ($metrics){
      $code = 200;
      $method = $request['method'];
      $description = null;
      if (isset($response['error_code'])) {
        $code = $response['error_code'];
        # We have to also include the description because TG overloads error codes
        $description = $response['description'];
      }
      $tg_response_code->inc([$code, $method, $description]);
    }
    // Validate response.
    if ((isset($response['ok']) && $response['ok'] != true) || isset($response['update_id'])) {
        if(is_array($json)) $json = json_encode($json);
        info_log("{$json} -> {$json_response}", 'ERROR:');
    } else {
        if($identifier != false) {
            if (isset($response['result']['chat']['type']) && in_array($response['result']['chat']['type'], ['channel','group','supergroup'])) {
                // Set chat and message_id
                $chat_id = $response['result']['chat']['id'];
                $message_id = $response['result']['message_id'];
                debug_log('Return data: Chat id: '.$chat_id.', message_id: '.$message_id.', type: '.(is_array($identifier) ? print_r($identifier,true) : $identifier));
                if($identifier == 'trainer') {
                    debug_log('Adding trainermessage info to database now!');
                    insert_trainerinfo($chat_id, $message_id);
                }else if ($identifier == 'overview') {
                    debug_log('Adding overview info to database now!');
                    $chat_title = $response['result']['chat']['title'];
                    $chat_username = isset($response['result']['chat']['username']) ? $response['result']['chat']['username'] : '';

                    insert_overview($chat_id, $message_id, $chat_title, $chat_username);
                }else {
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
                            if($photo['file_size'] > $largest_size) {
                                $largest_size = $photo['file_size'];
                                $save_id = $photo['file_id'];
                                $unique_id = $photo['file_unique_id'];
                            }
                        }
                        $standalone_photo = (array_key_exists('standalone_photo', $identifier) && $identifier['standalone_photo'] === true) ? true : false;
                        my_query("
                                    REPLACE INTO photo_cache
                                    VALUES (:id, :unique_id, :pokedex_id, :form_id, :raid_id, :ended, :gym_id, :standalone)
                                ",[
                                    ':id' => $save_id,
                                    ':unique_id' => $unique_id,
                                    ':pokedex_id' => $identifier['pokemon'],
                                    ':form_id' => $identifier['pokemon_form'],
                                    ':raid_id' => ($identifier['raid_ended'] ? 0 : $identifier['id']),  // No need to save raid id if raid has ended
                                    ':ended' => $identifier['raid_ended'],
                                    ':gym_id' => $identifier['gym_id'],
                                    ':standalone' => $standalone_photo,
                                ]
                            );
                    }
                    $raid_id = is_array($identifier) ? $identifier['id'] : $identifier;
                    insert_cleanup($chat_id, $message_id, $raid_id, $type, $unique_id);
                }
            }
        }
    }

    // Return response.
    return $response;
}

?>
