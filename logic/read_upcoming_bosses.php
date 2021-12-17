<?php
require_once(LOGIC_PATH . '/resolve_boss_name_to_ids.php');
/**
 * Read upcoming bosses from Pokebattlers API and return the results as a HTML formatted text list
 * @param bool $return_sql Return results in sql insert query instead of text list
 * @return string
 */
function read_upcoming_bosses($return_sql = false) {
    global $config;
    $link = curl_get_contents('https://fight.pokebattler.com/raids');
    $pb = json_decode($link,true);

    $now = new dateTimeZone($config->TIMEZONE);
    $transitions = ( $now->getTransitions()[0]['isdst'] ? 0 : 1 );
    $tz_offset = (-7 - $transitions)*60*60;
    $count = 0;
    $sql = $list = $prev_start = $prev_rl = '';
    foreach($pb['breakingNews'] as $news) {
        if($news['type'] == 'RAID_TYPE_RAID') {
            $rl = str_replace('RAID_LEVEL_','', $news['tier']);
            if($rl == "MEGA") $raid_level_id = 6; else $raid_level_id = $rl;
            if($raid_level_id != '5' and $raid_level_id != '6') break; // Limit scheduling to tier 5 and mega only
            $starttime = new DateTime("@".(substr($news['startDate'],0,10) + $tz_offset), new dateTimeZone('UTC'));
            $endtime = new DateTime("@".(substr($news['endDate'],0,10) + $tz_offset), new dateTimeZone('UTC'));

            // If the boss only appears for an hour, the eggs most likely start to spawn 20 minutes prior to the time.
            $diff = $starttime->diff($endtime);
            if($diff->format('%h') == 1) {
                $starttime->sub(new DateInterval('PT20M'));
            }
	    $date_start = $starttime->format('Y-m-d H:i:s');

            if($endtime->format('H') == '11') {
                // Usually the switch happens at 10. Pokebattler sets the end time to 11, so we must manually set it to 10
                $date_end = $endtime->format('Y-m-d').' 10:00:00';
            }else {
                $date_end = $endtime->format('Y-m-d H:i:s');
            }

            $dex_id_form = explode('-',resolve_boss_name_to_ids($news['pokemon']),2);
            if($prev_start != $date_start) {
                $list.= CR . '<b>' . $date_start . ' - ' . $date_end . ':</b>' . CR;
            }
            if($prev_rl != $raid_level_id) {
                $list.= '<b>' . getTranslation($raid_level_id . 'stars') .':</b>' . CR;
            }
            $list.= get_local_pokemon_name($dex_id_form[0], $dex_id_form[1]) . CR;
            $prev_start = $date_start;
            $prev_rl = $raid_level_id;

            if($count == 0) {
                $count++;
                $sql .= 'INSERT INTO raid_bosses (pokedex_id, pokemon_form_id, date_start, date_end, raid_level) VALUES ';
                $sql .= '("'.$dex_id_form[0].'","'.$dex_id_form[1].'","'.$date_start.'","'.$date_end.'","'.$raid_level_id.'")';
            }else {
                $sql .= ',("'.$dex_id_form[0].'","'.$dex_id_form[1].'","'.$date_start.'","'.$date_end.'","'.$raid_level_id.'")';
            }
        }
    }
    if($count > 0) $sql.=';';
    if($return_sql) return $sql;
    else return $list;
}
?>
