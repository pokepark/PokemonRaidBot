<?php
// Write to log.
debug_log('vote_want_invite()');

// For debug.
//debug_log($update);
//debug_log($data);

try {
    $query = "
            UPDATE    attendance
            SET     want_invite = CASE
                      WHEN want_invite = '0' THEN '1'
                      ELSE '0'
                    END,
                    late = 0,
                    arrived = 0,
                    remote = 0
            WHERE   raid_id = :raid_id
            AND   user_id = :user_id
            ";
    $statement = $dbh->prepare( $query );
    $statement->execute([
                    'raid_id' => $data['id'],
                    'user_id' => $update['callback_query']['from']['id']
                    ]);
    $query_select = "
            SELECT  want_invite
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
}
catch (PDOException $exception) {

    error_log($exception->getMessage());
    $dbh = null;
    exit;
}

if($res['want_invite'] == 1) {
    alarm($data['id'],$update['callback_query']['from']['id'],'want_invite');
} else {
    alarm($data['id'],$update['callback_query']['from']['id'],'no_want_invite');
}

// Send vote response.
if($config->RAID_PICTURE) {
    send_response_vote($update, $data,false,false);
} else {
    send_response_vote($update, $data);
} 

exit();