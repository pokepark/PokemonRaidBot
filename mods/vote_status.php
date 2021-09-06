<?php
// Write to log.
debug_log('vote_status()');

// For debug.
//debug_log($update);
//debug_log($data);
$remote_string = getPublicTranslation('remote_raid');

// Check if the user has voted for this raid before.
$rs = my_query(
    "
    SELECT    attendance.want_invite, attendance.alarm,
              IF(SUBSTR(gyms.gym_name, 1, LENGTH('".$remote_string."')-1) = '".$remote_string."', 1, 0)     as is_remote_gym,
              IF(raids.user_id = {$update['callback_query']['from']['id']}, 1, 0)                           as user_is_creator
    FROM      attendance
    LEFT JOIN raids
    ON        raids.id = attendance.raid_id
    LEFT JOIN gyms
    ON        gyms.id = raids.gym_id
      WHERE   attendance.raid_id = {$data['id']}
        AND   attendance.user_id = {$update['callback_query']['from']['id']}
    "
);

// Get the answer.
$answer = $rs->fetch();

// Write to log.
debug_log($answer);

// Telegram JSON array.
$tg_json = array();

// Get status to update
$status = $data['arg'];

// Make sure user has voted before.
if (!empty($answer)) {
    // Prevent invite beggars from voting late or arrived
    if(!($answer['want_invite'] == 1 && ($status == 'late' || $status == 'arrived'))) {
        // Update attendance.
        if($status == 'alarm') {
            // Enable / Disable alarm 
            my_query(
            "
            UPDATE attendance
            SET    alarm = CASE
                   WHEN alarm = '0' THEN '1'
                   ELSE '0'
                   END
            WHERE  raid_id = {$data['id']}
            AND    user_id = {$update['callback_query']['from']['id']}
            "
            );
            $new_alarm_value = $answer['alarm'] = 0 ? 1 : 0;
            // Inform User about change
            sendAlertOnOffNotice($data['id'], $update['callback_query']['from']['id'], $new_alarm_value);
        } else {
            // All other status-updates are using the short query
            my_query(
            "
            UPDATE  attendance
            SET     arrived = 0,
                    raid_done = 0,
                    cancel = 0,
                    late = 0,
                    $status = 1
            WHERE   raid_id = {$data['id']}
            AND     user_id = {$update['callback_query']['from']['id']}
            "
            );
            if($status == 'raid_done' or $status == 'cancel') {
                if($status == 'cancel') $tg_json = alarm($data['id'],$update['callback_query']['from']['id'],'status',$status, $tg_json);
                // If the gym is a temporary remote raid gym and raid creator voted for done, send message asking for raid deletion
                if($answer['is_remote_gym'] == '1' && $answer['user_is_creator']) {
                    $keys = [[
                                [
                                'text'          =>  getTranslation('yes'),
                                'callback_data' =>  $data['id'].':end_remote_raid:0'
                                ],
                                [
                                'text'          =>  getTranslation('no'),
                                'callback_data' =>  '0:exit:0'
                                ],
                             ]];
                    if($status == 'raid_done') $msg = getTranslation("delete_remote_raid_done");
                    else if($status == 'cancel') $msg = getTranslation("delete_remote_raid_cancel");
                    $tg_json[] = send_message($update['callback_query']['from']['id'], $msg, $keys, false, true);
                }
            }elseif($status != 'arrived') {
                $tg_json = alarm($data['id'],$update['callback_query']['from']['id'],'status',$status, $tg_json);
            }
        }

        // Send vote response.
        require_once(LOGIC_PATH . '/update_raid_poll.php');

        $tg_json = update_raid_poll($data['id'], false, $update, $tg_json);

        $tg_json[] = answerCallbackQuery($update['callback_query']['id'], getTranslation('vote_updated'), true);

        curl_json_multi_request($tg_json);
    }else {
        $msg = getTranslation('vote_status_not_allowed');
        answerCallbackQuery($update['callback_query']['id'], $msg);
    }
} else {
    // Send vote time first.
    send_vote_time_first($update);
}

exit();
