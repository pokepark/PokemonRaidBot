<?php
/**
 * Active raid duplication check.
 * @param int $gym_id Internal gym id
 * @param int $level (optional) raid level
 * @return int
 */
function active_raid_duplication_check($gym_id, $level = false)
{
  global $config;
  $levelSql = '';
  $args = [$gym_id];
  if($level !== false) {
    $levelSql = 'AND level = ?';
    $args[] = $level;
  }
  // Build query.
  $rs = my_query('
    SELECT id, event, level
    FROM   raids
    WHERE  end_time > (UTC_TIMESTAMP() - INTERVAL 5 MINUTE)
    AND  gym_id = ?
    ' . $levelSql . '
    ORDER BY end_time, event IS NOT NULL
    ', $args
  );
  while($raid = $rs->fetch()) {
    // In some cases (ex-raids, event raids and elite raids) gyms can have multiple raids saved to them.
    // We ignore these raids when performing the duplication check.
    if( ($config->RAID_EXCLUDE_EXRAID_DUPLICATION && $raid['event'] == EVENT_ID_EX)
     or ($level != 9 && $config->RAID_EXCLUDE_ELITE_DUPLICATION && $raid['level'] == 9)
     or ($config->RAID_EXCLUDE_EVENT_DUPLICATION && $raid['event'] !== NULL && $raid['event'] != EVENT_ID_EX)) {
      continue;
    }
    return $raid['id'];
  }
  return 0;
}
