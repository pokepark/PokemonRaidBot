<?php

// Refresh polls also
if($config->AUTO_REFRESH_POLLS) {
    $query_messages = my_query("
                    SELECT *
                    FROM cleanup
                    LEFT JOIN   raids
                    ON          cleanup.raid_id = raids.id
                    WHERE   chat_id != 0
                    AND     raids.start_time <= NOW()
                    AND     message_id != 0
                    ");
    debug_log("REFRESH POLLS:");
    debug_log("Num rows: ".$query_messages->rowCount());
    while($res_messages = $query_messages->fetch()) {
        debug_log("message id: ".$res_messages['message_id']);
        debug_log("chat id: ".$res_messages['chat_id']);
        debug_log("raid id: ".$res_messages['raid_id']);

        // Create a fake callback_query for edit_caption()
        $data_poll['callback_query']['message']['message_id']=$res_messages['message_id'];
        $data_poll['callback_query']['message']['chat']['id']=$res_messages['chat_id'];

        debug_log("callback_query: ".json_encode($data_poll));

        // Get the raid data by id.
        $raid = get_raid($res_messages['raid_id']);

        $msg = show_raid_poll($raid);
        $keys = keys_vote($raid);

        if(!$config->RAID_PICTURE or $raid['event_hide_raid_picture'] == 1 or strlen(utf8_decode($msg['short'])) > 1024) {
            edit_message($data_poll, $msg['full'], $keys, ['disable_web_page_preview' => 'true']);
        }else {
            // If raid is over, update photo
            $time_now = utcnow();
            if($time_now > $raid['end_time']) {
                // Edit the photo
                require_once(LOGIC_PATH . '/raid_picture.php');
                $raid['pokemon'] = 'ended';
                $picture_url = raid_picture_url($raid);
                editMessageMedia($res_messages['message_id'], $msg['short'], $keys, $res_messages['chat_id'],['disable_web_page_preview' => 'true'], false, $picture_url);
            }else {
                edit_caption($data_poll, $msg['short'], $keys, ['disable_web_page_preview' => 'true']);
            }
        }
    }
}
exit();