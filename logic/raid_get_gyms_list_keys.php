<?php
/**
 * Get gyms by searchterm.
 * @param $searchterm
 * @return bool|array
 */
function raid_get_gyms_list_keys($searchterm)
{
  // Get gyms from database
  $rs = my_query('
      SELECT  id, gym_name
      FROM    gyms
      WHERE   gym_name LIKE \'' . $searchterm . '%\'
      AND     show_gym LIKE 1
      OR      gym_name LIKE \'% ' .$searchterm . '%\'
      AND     show_gym LIKE 1
      ORDER BY
        CASE
        WHEN  gym_name LIKE \'' . $searchterm . '%\' THEN 1
        WHEN  gym_name LIKE \'%' . $searchterm . '%\' THEN 2
        ELSE  3
        END
      LIMIT   15
      '
  );
  // Init empty keys array.
  $keys = [];

  while ($gym = $rs->fetch()) {
    $first = strtoupper(substr($gym['gym_name'], 0, 1));
    $keys[] = button($gym['gym_name'], ['edit_raidlevel', 'g' => $gym['id'], 'fl' => $first]);
  }

  // Add abort key.
  if($keys) {
    // Get the inline key array.
    $keys = inline_key_array($keys, 1);
  }

  return $keys;
}
