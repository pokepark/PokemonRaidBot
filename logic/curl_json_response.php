<?php
/**
 * Process response from telegram api.
 * @param string $json_response Json response from Telegram
 * @param string $json Json sent to Telegram
 * @param int|string $identifier
 * @return mixed
 */
function curl_json_response($json_response, $json, $identifier = false)
{
    global $config;
    // Write to log.
    debug_log_incoming($json_response, '<-');

    // Decode json response.
    $response = json_decode($json_response, true);
    // Validate response.
    if ((isset($response['ok']) && $response['ok'] != true) || isset($response['update_id'])) {
        info_log("{$json} -> {$json_response}", 'ERROR:');
    } else {
        if($identifier != false) {
            if (isset($response['result']['chat']['type']) && in_array($response['result']['chat']['type'], ['channel','group','supergroup'])) {
                // Set chat and message_id
                $chat_id = $response['result']['chat']['id'];
                $message_id = $response['result']['message_id'];
                debug_log('Return data: Chat id: '.$chat_id.', message_id: '.$message_id.', type: '.$identifier);
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
                    insert_cleanup($chat_id, $message_id, $identifier, $type);
                }
            }
        }
    }

    // Return response.
    return $response;
}

?>
