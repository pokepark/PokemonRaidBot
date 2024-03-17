<?php
/**
 * Get gym by telegram id.
 * @param $id string
 * @return array
 */
function get_gym_by_telegram_id($id)
{
  // Get gym from database
  $rs = my_query('
    SELECT      *
    FROM        gyms
    WHERE       gym_name = ?
    ORDER BY    id DESC
    LIMIT       1
    ', [$id]
  );

  $gym = $rs->fetch();

  return $gym;
}
