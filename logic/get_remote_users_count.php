<?php
/**
 * Get current remote users count.
 * @param $raid_id
 * @param $user_id
 * @param $attend_time
 * @return int
 */
function get_remote_users_count($raid_id, $user_id, $attend_time = false)
{
    global $config;

    if(!$attend_time) {
        // If attend time is not given, get the one user has already voted for from database
        $att_sql = "(
                                SELECT    attend_time
                                FROM      attendance
                                WHERE     raid_id = {$raid_id}
                                    AND   user_id = {$user_id}
                                LIMIT     1
                            )";
    }else {
        // Use given attend time (needed when voting for new time)
        $att_sql = "'{$attend_time}'";
    }

    // Check if max remote users limit is already reached!
    // Ignore max limit if attend time is 'Anytime'
    $rs = my_query(
        "
        SELECT    IF(attend_time = '" . ANYTIME . "',0,sum(1 + extra_mystic + extra_valor + extra_instinct)) AS remote_users
        FROM      (SELECT DISTINCT user_id, extra_mystic, extra_valor, extra_instinct, remote, attend_time FROM attendance WHERE remote = 1 AND cancel = 0 AND raid_done = 0) as T
        WHERE     attend_time = {$att_sql}
        GROUP BY  attend_time
        "
    );

    // Get the answer.
    $answer = $rs->fetch();

    // Write to log.
    debug_log($answer['remote_users'], 'Remote participants so far:');
    debug_log($config->RAID_REMOTEPASS_USERS_LIMIT, 'Maximum remote participants:');

    return $answer['remote_users'];
}

?>
