<?php
// Write to log.
debug_log('vote_remote()');

// For debug.
//debug_log($update);
//debug_log($data);

// Get current remote status of user
$rs = my_query(
    "
    SELECT remote, (1 + extra_in_person + extra_alien) as user_count
    FROM   attendance
    WHERE  raid_id = {$data['id']}
    AND   user_id = {$update['callback_query']['from']['id']}
    "
);

// Get remote value.
$remote = $rs->fetch();
$remote_status = isset($remote['remote']) ? $remote['remote'] : 0;
$user_remote_count = isset($remote['user_count']) ? $remote['user_count'] : 0;

// Check if max remote users limit is already reached!
$remote_users = get_remote_users_count($data['id'], $update['callback_query']['from']['id']);

if($rs->rowCount() > 0) {
    // Ignore max users reached when switching from remote to local otherwise check if max users reached?
    if ($remote_users + $user_remote_count <= $config->RAID_REMOTEPASS_USERS_LIMIT || $remote_status == 1) {
        // Update users table.
        my_query(
            "
            UPDATE    attendance
            SET    remote = CASE
                    WHEN remote = '0' THEN '1'
                    ELSE '0'
                END,
                    want_invite = 0,
                    can_invite = 0
            WHERE   raid_id = {$data['id']}
            AND   user_id = {$update['callback_query']['from']['id']}
            "
        );

        if($remote_status == 0) {
            $tg_json = alarm($data['id'],$update['callback_query']['from']['id'],'remote');
        } else {
            $tg_json = alarm($data['id'],$update['callback_query']['from']['id'],'no_remote');
        }

        // Send vote response.
        require_once(LOGIC_PATH . '/update_raid_poll.php');

        $tg_json = update_raid_poll($data['id'], false, $update, $tg_json);

        $tg_json[] = answerCallbackQuery($update['callback_query']['id'], getTranslation('vote_updated'), true);

        curl_json_multi_request($tg_json);
    } else {
        // Send max remote users reached.
        send_vote_remote_users_limit_reached($update);
    }
} else {
    // Send vote time first.
    send_vote_time_first($update);
}

exit();
