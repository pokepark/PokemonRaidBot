<?php
$file_contents = file_get_contents( __DIR__ .'/config.json');

if(! is_string($file_contents)){
    die('Config file not readable, cannot continue');
}

$config = (Object)json_decode($file_contents, true);

if(json_last_error() !== JSON_ERROR_NONE) {
    die('Config file not valid JSON, cannot continue.');
}

$tz = $config->TIMEZONE;
date_default_timezone_set($tz);

// Establish mysql connection.
// TODO(artanicus): This should be centralized & imported instead of duplicated
$dbh = new PDO('mysql:host=' . $config->DB_HOST . ';dbname=' . $config->DB_NAME . ';charset=utf8mb4', $config->DB_USER, $config->DB_PASSWORD, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
$dbh->setAttribute(PDO::ATTR_ORACLE_NULLS, PDO::NULL_EMPTY_STRING);
$dbh->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$link = 'https://raw.githubusercontent.com/ccev/pogoinfo/v2/active/events.json';
$data = curl_get_contents($link);
$raids = $dbh->prepare("SELECT start_time FROM raids WHERE event = '" . $config->RAID_HOUR_EVENT_ID . "'");
$raids->execute();
$raids_res = $raids->fetchAll(PDO::FETCH_COLUMN,0);
$offset = date('Z');
$interval = DateInterval::createFromDateString($offset.' seconds');
$raid_to_create = [];
$datesToCreate = [];
$data = json_decode($data,true);
if($data !== false) {
    $now = new DateTime('18:00');
    $now->sub($interval);

    foreach($data as $event) {
        $start = new DateTime($event['start']);
        $start->sub($interval);
        $end = new DateTime($event['end']);
        $end->sub($interval);
        $event_start = $start->format('Y-m-d H:i:s');
        $event_end = $end->format('Y-m-d H:i:s');
        if($event['type'] == 'raid-hour' && (($raids->rowcount() > 0 && !in_array($event_start, $raids_res)) || $raids->rowcount() === 0)) {
            $mon_name = trim(str_replace('Raid Hour','',str_replace('Forme','',$event['name'])));
            $mon_split = explode(' ',$mon_name);
            $part_count = count($mon_split);
            $mon_query_input = [];
            if($part_count==1){ $mon_query_input = [$mon_split[0],'normal',$mon_split[0],'normal'];}
            else {
                $o=0;
                while($o<2) {
                    for($i=0;$i<$part_count;$i++) {
                        $mon_query_input[] = $mon_split[$i];
                    }
                    $o++;
                }
            }
            $in = str_repeat('?,',count($mon_query_input)/2-1).'?';

            $q = 'SELECT pokedex_id,pokemon_form_id FROM pokemon WHERE pokemon_name IN ('.$in.') AND pokemon_form_name IN ('.$in.')';
            $mon = $dbh->prepare($q);
            $mon->execute($mon_query_input);
            $mon_res = $mon->fetch();
            if($mon->rowcount() == 1) {
                $pokemon = $mon_res['pokedex_id'];
                $pokemon_form = $mon_res['pokemon_form_id'];
            }else {
                $mon = get_current_bosses($event_start);
                if($mon === false) continue;
                $pokemon = $mon[0];
                $pokemon_form = $mon[1];
            }
            $raid_to_create[] = [$pokemon, $pokemon_form,$event_start,$event_end];
            $datesToCreate[] = $event_start;
        }
    }
    if($now->format('w') == 3 && !in_array($now->format('Y-m-d H:i:s'), $raids_res) && !in_array($now->format('Y-m-d H:i:s'), $datesToCreate)) {
        $start_time = gmdate('Y-m-d H:i:s',mktime(18,0,0));
        $end_time = gmdate('Y-m-d H:i:s',mktime(19,0,0));
        $mon = get_current_bosses($start_time);
        if($mon !== false) $raid_to_create[] = [$mon[0], $mon[1] ,$start_time, $end_time];
    }
}
try {
    $query = $dbh->prepare('INSERT INTO raids (
                                user_id,
                                pokemon,
                                pokemon_form,
                                spawn,
                                level,
                                start_time,
                                end_time,
                                gym_id,
                                event,
                                event_note
                               )
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                          ');

    foreach($raid_to_create as $raid_info) {
        foreach($config->GYM_INFOS as $t) {
            $now = gmdate('Y-m-d H:i:s');
            $query->execute([$config->RAID_CREATOR_ID,$raid_info[0],$raid_info[1],$now,'5',$raid_info[2],$raid_info[3],$t[0],$config->RAID_HOUR_EVENT_ID,$t[1]]);
        }
    }
}
catch (PDOException $exception) {
    echo($exception->getMessage());
    exit;
}
function get_current_bosses($spawn) {
    global $dbh;
    $i = 0;
    $levels = [5, 8]; // Search potential raid hour bosses from these raid levels
    $pokemon = $pokemon_form = false;
    do {
        $pk = $dbh->prepare('SELECT pokedex_id,pokemon_form_id FROM raid_bosses WHERE raid_level = ? AND ? BETWEEN date_start AND date_end');
        $pk->execute([$levels[$i], $spawn]);
        $res = $pk->fetch();
        if($pk->rowCount() == 1) {
            $pokemon = $res['pokedex_id'];
            $pokemon_form = $res['pokemon_form_id'];
        }elseif($pk->rowCount() > 1) {
            $pokemon = 999 . $levels[$i];
            $pokemon_form = 0;
        }
        $i++;
    } while($pk->rowcount() > 0 or $i <= 1);
    if($pokemon === false) return false;
    return [$pokemon,$pokemon_form];
}
function curl_get_contents($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)' );
    $content = curl_exec($ch);
    // Check if any error occurred
    if(curl_errno($ch))
    {
        echo 'Curl error: ' . curl_error($ch);
        $content = false;
    }
    curl_close($ch);
    return $content;
}
