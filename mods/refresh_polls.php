<?php

// Refresh polls
if($config->AUTO_REFRESH_POLLS) {
    if(is_numeric($data['id'])) $where_chat = 'chat_id = '.$data['id']; else $where_chat = 'chat_id != 0';
    $query_messages = my_query("
                    SELECT      cleanup.*
                    FROM        cleanup
                    LEFT JOIN   raids
                    ON          cleanup.raid_id = raids.id
                    WHERE   {$where_chat}
                    AND     cleanup.type IN ('poll_text', 'poll_photo')
                    AND     raids.start_time <= UTC_TIMESTAMP()
                    AND     raids.end_time > DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 MINUTE)
                    AND     message_id != 0
                    AND     raids.level NOT IN ({$config->RAID_POLL_HIDE_BUTTONS_RAID_LEVEL})
                    ");
    debug_log("REFRESH POLLS:");
    debug_log("Num rows: ".$query_messages->rowCount());
    $tg_json = [];
    while($res_messages = $query_messages->fetch()) {

        debug_log("message id: ".$res_messages['message_id']);
        debug_log("chat id: ".$res_messages['chat_id']);
        debug_log("raid id: ".$res_messages['raid_id']);

        $data_poll['push']['message_id']=$res_messages['message_id'];
        $data_poll['push']['chat_id']=$res_messages['chat_id'];
        $data_poll['push']['type']=$res_messages['type'];

        require_once(LOGIC_PATH . '/update_raid_poll.php');
        $tg_json = update_raid_poll($res_messages['raid_id'], false, $data_poll, $tg_json, false);
    }
    curl_json_multi_request($tg_json);
}
exit();