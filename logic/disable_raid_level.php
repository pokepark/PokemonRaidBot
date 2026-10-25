<?php
/**
 * Disable raids for level.
 * @param string $levels Comma separated list of levels
 * @return
 */
function disable_raid_level($levels)
{
  // Get gym from database
  my_query('
    DELETE FROM raid_bosses
    WHERE     raid_level IN (' . $levels . ')
    AND       scheduled = 0
  ');
}
