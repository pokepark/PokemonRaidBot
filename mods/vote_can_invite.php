<?php
// Write to log.
debug_log('vote_can_invite()');

// For debug.
//debug_log($update);
//debug_log($data);

try {
    $query_select = "
            SELECT  can_invite
            FROM    attendance
            WHERE   raid_id = :raid_id
            AND   user_id = :user_id
            LIMIT 1
            ";
    $statement_select = $dbh->prepare( $query_select );
    $statement_select->execute([
                    'raid_id' => $data['id'],
                    'user_id' => $update['callback_query']['from']['id']
                    ]);
    $res = $statement_select->fetch();
    if($statement_select->rowCount() > 0) {
        $query = "
                UPDATE    attendance
                SET     can_invite = CASE
                          WHEN can_invite = '0' THEN '1'
                          ELSE '0'
                        END,
                        late = 0,
                        arrived = 0,
                        remote = 0,
                        want_invite = 0,
                        extra_alien = 0,
                        extra_in_person = 0
                WHERE   raid_id = :raid_id
                AND   user_id = :user_id
                ";
        $statement = $dbh->prepare( $query );
        $statement->execute([
                        'raid_id' => $data['id'],
                        'user_id' => $update['callback_query']['from']['id']
                        ]);
    }
}
catch (PDOException $exception) {

    error_log($exception->getMessage());
    $dbh = null;
    exit;
}
if($statement_select->rowCount() > 0) {
    if($res['can_invite'] == 0) {
        $alarm_action = 'can_invite';
    } else {
        $alarm_action = 'no_can_invite';
    }
    // Send vote response.
    require_once(LOGIC_PATH . '/update_raid_poll.php');

    $tg_json = update_raid_poll($data['id'], false, $update);

    $tg_json = alarm($data['id'],$update['callback_query']['from']['id'], $alarm_action, '', $tg_json);

    $tg_json[] = answerCallbackQuery($update['callback_query']['id'], getTranslation('vote_updated'), true);

    curl_json_multi_request($tg_json);
} else {
    // Send vote time first.
    send_vote_time_first($update);
}

$dbh = null;
exit();
