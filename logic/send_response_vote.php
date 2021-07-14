<?php
/**
 * Send response vote.
 * @param $update
 * @param $data
 * @param bool $new
 */
function send_response_vote($update, $data, $new = false, $text = true)
{
    global $config;
    // get all channel, where the raid polls where shared
    $chats_to_update = get_all_active_raid_channels($update, $data);
    // Initial text status
    $initial_text = $text;
    // Get the raid data by id.
    $raid = get_raid($data['id']);
    // Message - make sure to not exceed Telegrams 1024 characters limit for caption
    $msg = show_raid_poll($raid);
    $full_msg = $msg['full'];
    $msg_full_len = strlen(utf8_decode($msg['full']));
    debug_log($msg_full_len, 'Raid poll full message length:');
    if(array_key_exists('short', $msg)) {
        $msg_short_len = strlen(utf8_decode($msg['short']));
        debug_log($msg_short_len, 'Raid poll short message length:');
        // Message short enough?
        if($msg_short_len < 1024) {
            $msg = $msg['short'];
        } else {
            // Use full text and reset text to true regardless of prior value
            $msg = $msg['full'];
            $text = true;
        }
    } else {
        // Use full text and reset text to true regardless of prior value
        $msg = $msg['full'];
        $text = true;
    }
    $keys = keys_vote($raid);

    // Write to log.
    // debug_log($keys);

    if ($new) {
        $loc = send_location($update['callback_query']['message']['chat']['id'], $raid['lat'], $raid['lon']);

        // Write to log.
        debug_log('location:');
        debug_log($loc);

        // Telegram JSON array.
        $tg_json = array();

        // Send the message.
        $tg_json[] = send_message($update['callback_query']['message']['chat']['id'], $msg . "\n", $keys, ['disable_web_page_preview' => 'true'], true);

        // Answer the callback.
        $tg_json[] = answerCallbackQuery($update['callback_query']['id'], $msg, true);

    } else {
        // Change message string.
        $callback_msg = getPublicTranslation('vote_updated');

        // Telegram JSON array.
        $tg_json = array();

        // Answer the callback.
        $tg_json[] = answerCallbackQuery($update['callback_query']['id'], $callback_msg, true, true);

        foreach($chats_to_update as $chat_id_msg_id) {
            $chat = $chat_id_msg_id['chat_id'];
            $message = $chat_id_msg_id['message_id'];
            // Modify the $update to also handle messages in other chats than the one where the vote came from
            $update['callback_query']['message']['message_id'] = $message;
            $update['callback_query']['message']['chat']['id'] = $chat;
            if($text) {
                // Make sure to only send if picture with caption and not text message
                if($initial_text == false && !(isset($update['callback_query']['message']['text']))) {
                    // Delete raid picture and caption.
                    delete_message($chat, $message);

                    // Resend raid poll as text message.
                    $tg_json[] = send_message($chat, $full_msg . "\n", $keys, ['disable_web_page_preview' => 'true'], true);
                } else {
                    // Edit the message.
                    $tg_json[] = edit_message($update, $full_msg, $keys, ['disable_web_page_preview' => 'true'], true);
                }
            } else {
                // Make sure it's a picture with caption.
                if(isset($update['callback_query']['message']['text'])) {
                    // Do not switch back to picture with caption. Only allow switch from picture with caption to text message.
                    // Edit the message.
                    $tg_json[] = edit_message($update, $full_msg, $keys, ['disable_web_page_preview' => 'true'], true);
                } else {
                    // Edit the caption.
                    $tg_json[] = edit_caption($update, $msg, $keys, ['disable_web_page_preview' => 'true'], true);

                    // Edit the picture - raid ended.
                    $time_now = utcnow();
                    if($time_now > $raid['end_time'] && $data['arg'] == 0) {
                        // TODO(artanicus): There's no logic for when RAID_PICTURE is not enabled, which is probably bad
                        require_once(LOGIC_PATH . '/raid_picture.php');
                        $raid['pokemon'] = 'ended';
                        $picture_url = raid_picture_url($raid);
                        $tg_json[] = editMessageMedia($message, $msg, $keys, $chat, ['disable_web_page_preview' => 'true'], false, $picture_url);
                    }
                }
            }
        }
    }

    // Telegram multicurl request.
    curl_json_multi_request($tg_json);

    // Exit.
    $dbh = null;
    exit();
}

/**
 * Delivers all Raid channel got to be updated
 * @param $update
 * @param $data
 * @return array $channel_id
 */

function get_all_active_raid_channels($update,$data){
    global $config;
    $channel_id = [[
        'chat_id' => $update['callback_query']['message']['chat']['id'],
        'message_id' => $update['callback_query']['message']['message_id'],
      ]];
    // TODO(artanicus): It's probably a bug that we have cleanup entries with chat_id = 0
    // but dropping them from the query at least stops them from hurting us
    $rs_chann = my_query(
        "
        SELECT *
        FROM cleanup
          WHERE raid_id = {$data['id']}
          AND cleaned = 0
          AND chat_id <> 0
        ");
    // IF Chat was shared only to target channel -> no extra update
    if ($rs_chann->rowCount() > 1) {
        // share to multiple chats
        $answer = $rs_chann->fetchAll();
        foreach($answer as $channel){
            array_push($channel_id,['chat_id' => $channel['chat_id'],'message_id' => $channel['message_id']]);
        }
        return $channel_id;
    }else{
        // Only one Chat to update
        return $channel_id;
    }
}

?>
