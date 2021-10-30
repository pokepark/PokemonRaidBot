<?php
// Write to log.
debug_log('pokebattler()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
bot_access_check($update, 'pokedex');
include(LOGIC_PATH . '/resolve_boss_name_to_ids.php');

$id = $data['id'];
$arg = $data['arg'];

if($arg == '1') {
    try {
        $sql = 'DELETE FROM raid_bosses WHERE date_start != "1970-01-01 00:00:01" AND date_end != "2038-01-19 03:14:07";';
        $sql .= read_upcoming_bosses(true);
        $query = $dbh->prepare($sql);
        $query->execute();
        $msg = getTranslation('import_done') . '!';
    }catch (PDOException $exception) {
        $msg = getTranslation('internal_error') . CR;
        $msg.= $exception->getMessage();
        info_log($exception->getMessage());
    }
    $keys = [];
}else {
    $list = read_upcoming_bosses();
    $msg = '';
    if(!empty($list)) {
        $now = new DateTime('now', new DateTimeZone($config->TIMEZONE));
        $query = my_query("
                SELECT * FROM raid_bosses
                WHERE       date_end > '" . $now->format('Y-m-d H:i:s') . "'
                AND         date_end <> '2038-01-19 03:14:07'
                ORDER BY    date_start, raid_level, pokedex_id, pokemon_form_id
                ");
        $prev_start = $prev_rl = '';
        $msg = '<b><u>' . getTranslation('current_scheduled_bosses') . ':</u></b>';
        foreach($query->fetchAll() as $result) {
            if($prev_start != $result['date_start']) {
                $msg.= CR . '<b>' . $result['date_start'] . ' - ' . $result['date_end'] . ':</b>' . CR;
            }
            if($prev_rl != $result['raid_level']) {
                $msg.= '<b>' . getTranslation($result['raid_level'] . 'stars') .':</b>' . CR;
            }
            $msg.= get_local_pokemon_name($result['pokedex_id'], $result['pokemon_form_id']) . CR;
            $prev_start = $result['date_start'];
            $prev_rl = $result['raid_level'];
        }
        $msg .= CR . CR . '<b><u>' . getTranslation('found_upcoming_bosses') . ':</u></b>';
        $msg .= $list;
        $msg .= CR . CR . getTranslation('confirm_raplace_upcoming');
        $keys = [
            [
                [
                    'text' => getTranslation('replace'), 
                    'callback_data' => '1:import_future_bosses:1'
                ]
            ],
            [
                [
                    'text'=>getTranslation('back'),
                    'callback_data' => '0:pokedex_import:0'
                ]
            ]
        ];
    }else {
        $msg .= getTranslation('upcoming_bosses_not_found');
        $keys = [
            [
                [
                    'text' => getTranslation('done'),
                    'callback_data' => '0:exit:1'
                ]
            ]
        ];
    }
}

// Callback message string.
$callback_response = 'OK';

// Telegram JSON array.
$tg_json = array();

// Answer callback.
$tg_json[] = answerCallbackQuery($update['callback_query']['id'], $callback_response, true);

// Edit message.
$tg_json[] = edit_message($update, $msg, $keys, false, true);

// Telegram multicurl request.
curl_json_multi_request($tg_json);

function read_upcoming_bosses($return_sql = false) {
    $link = curl_get_contents('https://fight.pokebattler.com/raids');
    $pb = json_decode($link,true);
    
    $ph = new dateTimeZone('America/Phoenix');
    $count = 0;
    $sql = $list = $prev_start = $prev_rl = '';
    foreach($pb['breakingNews'] as $news) {
        if($news['type'] == 'RAID_TYPE_RAID') {
            $rl = str_replace('RAID_LEVEL_','', $news['tier']);
            if($rl == "MEGA") $raid_level_id = 6; else $raid_level_id = $rl;
            $starttime = new DateTime("@".substr($news['startDate'],0,10));
            $endtime = new DateTime("@".substr($news['endDate'],0,10));
            $starttime->setTimezone($ph);
            $endtime->setTimezone($ph);
            // Pokebattler sets end time to 11:00, so lets just manually set everything to 10:00
            $date_start = $starttime->format('Y-m-d').' 10:00:00';
            $date_end = $endtime->format('Y-m-d').' 10:00:00';

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

$dbh = null;
exit();

?>