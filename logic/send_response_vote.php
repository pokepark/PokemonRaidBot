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
        $tg_json[] = send_message($update['callback_query']['message']['chat']['id'], $msg . "\n", $keys, ['disable_web_page_preview' => 'true', 'reply_to_message_id' => $loc['result']['message_id']], true);

        // Answer the callback.
        $tg_json[] = answerCallbackQuery($update['callback_query']['id'], $msg, true);

    } else {
        // Change message string.
        $callback_msg = getPublicTranslation('vote_updated');

        // Telegram JSON array.
        $tg_json = array();

        // Answer the callback.
        $tg_json[] = answerCallbackQuery($update['callback_query']['id'], $callback_msg, true, true);

	if($text) {
            // Make sure to only send if picture with caption and not text message
            if($initial_text == false && !(isset($update['callback_query']['message']['text']))) {
                // Delete raid picture and caption.
                delete_message($update['callback_query']['message']['chat']['id'], $update['callback_query']['message']['message_id']);

                // Resend raid poll as text message.
                $tg_json[] = send_message($update['callback_query']['message']['chat']['id'], $full_msg . "\n", $keys, ['disable_web_page_preview' => 'true'], true);
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
	            $tg_json[] = editMessageMedia($update['callback_query']['message']['message_id'], $msg, $keys, $update['callback_query']['message']['chat']['id'], ['disable_web_page_preview' => 'true'], false, $picture_url);
                }
            }
	}
    }

    // Telegram multicurl request.
    curl_json_multi_request($tg_json);

    // Exit.
    exit();
}

?>
