<?php
require_once(LOGIC_PATH . '/resolve_boss_name_to_ids.php');
/**
 * Read upcoming bosses from Pokebattlers API and return the results as a HTML formatted text list
 * @param bool $return_sql Return results in sql insert query instead of text list
 * @return string
 */
function read_upcoming_bosses($return_sql = false) {
  global $pokebattler_import_future_tiers, $pokebattler_level_map, $pokebattler_pokemon_map;
  $link = curl_get_contents('https://fight.pokebattler.com/raids');
  $pb = json_decode($link, true);
  if(!isset($pb['breakingNews'])) return '';

  $pb_timezone = new dateTimeZone('America/Los_Angeles');
  $standardTimezone = new dateTimeZone('UTC');
  $count = 0;
  $sql = $list = $prev_start = $prev_end = $prev_rl = '';
  foreach($pb['breakingNews'] as $news) {
    if($news['type'] != 'RAID_TYPE_RAID') continue;

    $rl = str_replace('RAID_LEVEL_','', $news['tier']);
    $raid_level_id = array_search($rl, $pokebattler_level_map);
    if(!in_array($raid_level_id, $pokebattler_import_future_tiers)) continue; // Limit scheduling to tier 5 and higher only
    $starttime = new DateTime("@".(substr($news['startDate'],0,10)), $standardTimezone);
    $endtime = new DateTime("@".(substr($news['endDate'],0,10)), $standardTimezone);

    $starttime->setTimezone($pb_timezone);
    $endtime->setTimezone($pb_timezone);

    // If the boss only appears for an hour, the eggs most likely start to spawn 20 minutes prior to the time.
    $diff = $starttime->diff($endtime);
    if($diff->format('%h') == 1) {
      $starttime->sub(new DateInterval('PT20M'));
    }

    $date_start = $starttime->format('Y-m-d H:i:s');
    $date_end = $endtime->format('Y-m-d H:i:s');

    $boss = $news['pokemon'];
    if(in_array($news['pokemon'], array_keys($pokebattler_pokemon_map))) {
      $boss = $pokebattler_pokemon_map[$news['pokemon']];
    }
    $dex_id_form = explode('-',resolve_boss_name_to_ids($boss),2);
    if($prev_start != $date_start or $prev_end != $date_end) {
      $list.= CR . EMOJI_CLOCK . ' <b>' . $starttime->format('j.n. ') . getTranslation('raid_egg_opens_at') . $starttime->format(' H:i') . ' —  ' .  $endtime->format('j.n. ') . getTranslation('raid_egg_opens_at') . $endtime->format(' H:i') . ':</b>' . CR;
      $prev_rl = '';
    }
    if($prev_rl != $raid_level_id) {
      $list.= '<b>' . getTranslation($raid_level_id . 'stars') .':</b>' . CR;
    }
    $list.= get_local_pokemon_name($dex_id_form[0], $dex_id_form[1]) . CR;
    $prev_start = $date_start;
    $prev_end = $date_end;
    $prev_rl = $raid_level_id;

    if($count == 0) {
      $count++;
      $sql .= 'INSERT INTO raid_bosses (pokedex_id, pokemon_form_id, date_start, date_end, raid_level, scheduled) VALUES ';
      $sql .= '("'.$dex_id_form[0].'","'.$dex_id_form[1].'","'.$date_start.'","'.$date_end.'","'.$raid_level_id.'", 1)';
    }else {
      $sql .= ',("'.$dex_id_form[0].'","'.$dex_id_form[1].'","'.$date_start.'","'.$date_end.'","'.$raid_level_id.'", 1)';
    }
  }
  if($count > 0) $sql.=';';

  if($return_sql) return $sql;
  else return $list;
}
?>