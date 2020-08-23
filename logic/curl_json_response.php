<?php
/**
 * Process response from telegram api.
 * @param $json
 * @param $json_response
 * @return mixed
 */
function curl_json_response($json_response, $json)
{
    global $config;
    // Write to log.
    debug_log($json_response, '<-');

    // Decode json response.
    $response = json_decode($json_response, true);

    // Validate response.
    if ($response['ok'] != true || isset($response['update_id'])) {
        // Write error to log.
        debug_log('ERROR: ' . $json . "\n\n" . $json_response . "\n\n");
    } else {
	// Result seems ok, get message_id and chat_id if supergroup or channel message
	if (isset($response['result']['chat']['type']) && ($response['result']['chat']['type'] == "channel" || $response['result']['chat']['type'] == "supergroup")) {
            // Init cleanup_id
            $cleanup_id = 0;

	    // Set chat and message_id
            $chat_id = $response['result']['chat']['id'];
            $message_id = $response['result']['message_id'];

            // Get raid id from $json
            $json_message = json_decode($json, true);

            // Write to log that message was shared with channel or supergroup
            debug_log('Message was shared with ' . $response['result']['chat']['type'] . ' ' . $response['result']['chat']['title']);
            debug_log('Checking input for cleanup info now...');

	    // Check if callback_data is present to get the cleanup id
            if (!empty($response['result']['reply_markup']['inline_keyboard']['0']['0']['callback_data'])) {
                debug_log('Callback Data of this message likely contains cleanup info!');
                $split_callback_data = explode(':', $response['result']['reply_markup']['inline_keyboard']['0']['0']['callback_data']);
	        // Get raid_id, but check for $config->BRIDGE_MODE first
	        if($config->BRIDGE_MODE) {
		    $cleanup_id = $split_callback_data[1];
		} else {
		    $cleanup_id = $split_callback_data[0];
	        }

            // Check if it's a venue and get raid id
            } else if (isset($response['result']['venue']['address']) && !empty($response['result']['venue']['address'])) {
                // Get raid_id from address.
                debug_log('Venue address message likely contains cleanup info!');
                if(strpos($response['result']['venue']['address'], substr(strtoupper($config->BOT_ID), 0, 1) . '-ID = ') !== false) {
                    $cleanup_id = substr($response['result']['venue']['address'],strpos($response['result']['venue']['address'], substr(strtoupper($config->BOT_ID), 0, 1) . '-ID = ') + 7);
                } else {
                    debug_log('BOT_ID ' . $config->BOT_ID . ' not found in venue address message!');
                }

            // Check if it's a text and get raid id
            } else if (!empty($response['result']['text'])) {
                debug_log('Text message likely contains cleanup info!');
                if(isset($response['result']['venue']['address']) && strpos($response['result']['venue']['address'], substr(strtoupper($config->BOT_ID), 0, 1) . '-ID = ') !== false) {
                    $cleanup_id = substr($response['result']['text'],strpos($response['result']['text'], substr(strtoupper($config->BOT_ID), 0, 1) . '-ID = ') + 7);
                } else if(strpos($response['result']['text'], substr(strtoupper($config->BOT_ID), 0, 1) . '-ID = ') !== false) {
                    $cleanup_id = substr($response['result']['text'],strpos($response['result']['text'], substr(strtoupper($config->BOT_ID), 0, 1) . '-ID = ') + 7);
                }else {
                    debug_log('BOT_ID ' . $config->BOT_ID . ' not found in text message!');
                }
            // Check if it's a caption and get raid id
            } else if (!empty($response['result']['caption'])) {
                debug_log('Caption in a message likely contains cleanup info!');
                if(strpos($response['result']['caption'], substr(strtoupper($config->BOT_ID), 0, 1) . '-ID = ') !== false) {
                    $cleanup_id = substr($response['result']['caption'],strpos($response['result']['caption'], substr(strtoupper($config->BOT_ID), 0, 1) . '-ID = ') + 7);
                } else {
                    debug_log('BOT_ID ' . $config->BOT_ID . ' not found in caption of message!');
                }
            }
            debug_log('Cleanup ID: ' . $cleanup_id);

            // Trigger Cleanup when raid_id was found
            if($cleanup_id != 0 && $cleanup_id != 'trainer') {
                debug_log('Found ID for cleanup preparation from callback_data or venue!');
                debug_log('Chat_ID: ' . $chat_id);
                debug_log('Message_ID: ' . $message_id);

	        // Trigger cleanup preparation process when necessary id's are not empty and numeric
	        if (!empty($chat_id) && !empty($message_id) && !empty($cleanup_id)) {
		    debug_log('Calling cleanup preparation now!');
		    insert_cleanup($chat_id, $message_id, $cleanup_id);
	        } else {
		    debug_log('Missing input! Cannot call cleanup preparation!');
		}
            } else if($cleanup_id != '0' && $cleanup_id == 'trainer') {
                debug_log('Detected trainer info message from callback_data!');
                debug_log('Chat_ID: ' . $chat_id);
                debug_log('Message_ID: ' . $message_id);

                // Add trainer info message details to database.
                if (!empty($chat_id) && !empty($message_id)) {
                    debug_log('Adding trainer info to database now!');
                    insert_trainerinfo($chat_id, $message_id);
                } else {
                    debug_log('Missing input! Cannot add trainer info!');
                }
            } else {
                debug_log('No cleanup info found! Skipping cleanup preparation!');
            }

            // Check if text starts with getTranslation('raid_overview_for_chat') and inline keyboard is empty
            $translation = !empty($config->LANGUAGE_PUBLIC) ? getPublicTranslation('raid_overview_for_chat') : '';
            $translation_length = strlen($translation);
            $text = !empty($response['result']['text']) ? substr($response['result']['text'], 0, $translation_length) : '';
            // Add overview message details to database.
            if (!empty($text) && !empty($translation) && $text === $translation && empty($json_message['reply_markup']['inline_keyboard'])) {
                debug_log('Detected overview message!');
                debug_log('Text: ' . $text);
                debug_log('Translation: ' . $translation);
                debug_log('Chat_ID: ' . $chat_id);
                debug_log('Message_ID: ' . $message_id);

                // Write raid overview data to database
                debug_log('Adding overview info to database now!');
                insert_overview($chat_id, $message_id);
            }
	}
    }

    // Return response.
    return $response;
}

?>
