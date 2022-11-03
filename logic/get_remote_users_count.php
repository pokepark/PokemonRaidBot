<?php
/**
 * Get current remote users count.
 * @param $raid_id
 * @param $user_id
 * @param $attend_time
 * @return int
 */
function get_remote_users_count($raidId, $userId, $attendTime = false)
{
  global $config;

  $attBinds['userId'] = $userId;
  if($attendTime) {
    $attSql = ':attTime';
    $attBinds['attTime'] = $attendTime;
  }else {
    // If attend time is not given, get the one user has already voted for from database
    $attSql = '(
      SELECT  attend_time
      FROM    attendance
      WHERE   raid_id = :raidId
        AND   user_id = :userId
      LIMIT   1
      )';
    $attBinds['raidId'] = $raidId;
    $attBinds['userId'] = $userId;
  }

  // Check if max remote users limit is already reached!
  // Ignore max limit if attend time is 'Anytime'
  $rs = my_query(
    '
    SELECT  CASE WHEN attend_time = \'' . ANYTIME . '\'
        THEN 0
        ELSE
          sum(CASE WHEN remote = 1 THEN 1 + extra_in_person ELSE 0 END + extra_alien) END AS remote_users
    FROM    (SELECT DISTINCT user_id, extra_in_person, extra_alien, remote, attend_time FROM attendance WHERE (remote = 1 or extra_alien > 0) AND cancel = 0 AND raid_done = 0 and user_id != :userId) as T
    WHERE   attend_time = ' . $attSql . '
    GROUP BY  attend_time
    ', $attBinds
  );

  // Get the answer.
  $answer = $rs->fetch();
  $remoteUsers = empty($answer) ? 0 : $answer['remote_users'];

  // Write to log.
  debug_log($remoteUsers, 'Remote participants so far:');
  debug_log($config->RAID_REMOTEPASS_USERS_LIMIT, 'Maximum remote participants:');

  return $remoteUsers;
}

?>
