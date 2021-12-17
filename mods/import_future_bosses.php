<?php
// Write to log.
debug_log('pokebattler()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
bot_access_check($update, 'pokedex');
require_once(LOGIC_PATH . '/read_upcoming_bosses.php');

$id = $data['id'];
$arg = $data['arg'];

if($arg == '1') {
    try {
        $sql = 'DELETE FROM raid_bosses WHERE date_start != "1970-01-01 00:00:01" AND date_end != "2038-01-19 03:14:07";';
        $sql .= read_upcoming_bosses(true);
        $query = $dbh->prepare($sql);
        $query->execute();
        $msg = getTranslation('import_done');
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
        $msg .= CR . CR . getTranslation('confirm_replace_upcoming');
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

$dbh = null;
exit();

<<<<<<< HEAD
?>
=======
?>
>>>>>>> 974937cda6355f2a888cf0c1ac394214e6980f17
