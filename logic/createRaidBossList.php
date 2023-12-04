<?php
require_once(LOGIC_PATH . '/get_raid_times.php');

/**
 * Prints a list of saved raid bosses in this format
 * - Darkrai 20.10. - 2.11.
 * - Lugia 28.10. - 29.10.
 * - Genesect Douse 2.11. - 9.11.
 * - Virizion 9.11. - 16.11.
 * - Cobalion, Terrakion 16.11. - 23.11.
 *
 * 28.10.
 * - Mewtwo 10:00 - 10:00
 * - Mewtwo 10:00 - 11:00
 *
 * 29.10.
 * - Moltres, Lugia 20:00 - 21:00
 * @return string
 */
function createRaidBossList() {
  global $config;
  $dateFormat = 'j.m.';
  $timeFormat = 'H:i';
  $levelList = '(' . implode(',', $config->RAID_BOSS_LIST_RAID_LEVELS). ')';
  $q = my_query('
    SELECT
      pokedex_id, pokemon_form_id, date_start, date_end,
      CONCAT(DATE_FORMAT(date_start,"%d%m%y%k"), DATE_FORMAT(date_end,"%d%m%y%k")) AS arrkey,
      CASE WHEN date(date_start) = date(date_end) THEN 1 ELSE 0 END AS sameDay
    FROM  raid_bosses
    WHERE raid_level IN ' . $levelList . '
    AND   date_end > DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ORDER BY sameDay, date_start, date_end
  ');
  $list = '';
  $prevStartDate = '';
  $data[0] = $data[1] = [];
  // Save the results in easy to process format
  foreach($q->fetchAll() as $row) {
    $data[$row['sameDay']][$row['arrkey']][] = $row;
  }
  if($q->rowCount() == 0) return '';
  $i = 1;
  $list = $config->RAID_BOSS_LIST_TITLE;
  // Print list of bosses that run for multiple days
  foreach($data[0] as $tempRow) {
    $list .= PHP_EOL . '- ';
    foreach($tempRow as $num => $row) {
      $pokemonName = get_local_pokemon_name($row['pokedex_id'], $row['pokemon_form_id']);
      if($num != 0) $list .= ', ';
      $list .= $pokemonName;
    }
    $dateStart = new dateTime($row['date_start']);
    $dateEnd = new dateTime($row['date_end']);
    $list .= ' ' . $dateStart->format($dateFormat) . ' - '. $dateEnd->format($dateFormat);
    $i++;
    if($i > $config->RAID_BOSS_LIST_ROW_LIMIT) break;
  }

  // Print list of one day bosses
  foreach($data[1] as $arrkey => $tempRow) {
    $startDate = substr($arrkey, 0, 6);
    if($list != '' && $prevStartDate != $startDate) $list.= PHP_EOL . PHP_EOL;
    foreach($tempRow as $num => $row) {
      $dateStart = new dateTime($row['date_start']);
      $dateEnd = new dateTime($row['date_end']);
      if($num == 0){
        if($prevStartDate != $startDate) {
          $list .= $dateStart->format($dateFormat);
        }
        $list .=  PHP_EOL . '- ';
      }
      $pokemonName = get_local_pokemon_name($row['pokedex_id'], $row['pokemon_form_id']);
      if($num != 0) $list .= ', ';
      $list .= $pokemonName;
      $prevStartDate = $startDate;
    }
    $list .= ' ' . $dateStart->format($timeFormat) . ' - ' . $dateEnd->format($timeFormat);
  }
  return $list;
}
?>