<?php
// Write to log.
debug_log('pokebattler()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
$botUser->accessCheck('pokedex');
require_once(LOGIC_PATH . '/read_upcoming_bosses.php');

$id = $data['id'];
$arg = $data['arg'];

if($arg == '1') {
  $sql = 'DELETE FROM raid_bosses WHERE scheduled = 1;';
  $sql .= read_upcoming_bosses(true);
  $query = my_query($sql);
  $msg = getTranslation('import_done');
  $keys = [];
}else {
  $list = read_upcoming_bosses();
  $msg = '';
  if(!empty($list)) {
    $now = new DateTime('now', new DateTimeZone($config->TIMEZONE));
    $query = my_query("
        SELECT id, pokedex_id, pokemon_form_id, raid_level, scheduled, DATE_FORMAT(date_start, '%e.%c. ".getTranslation('raid_egg_opens_at')." %H:%i') as date_start, DATE_FORMAT(date_end, '%e.%c. ".getTranslation('raid_egg_opens_at')." %H:%i') as date_end FROM raid_bosses
        WHERE     date_end > '" . $now->format('Y-m-d H:i:s') . "'
        AND     scheduled = 1
        ORDER BY  date_start, raid_level, pokedex_id, pokemon_form_id
        ");
    $prev_start = $prev_rl = '';
    $msg = '<b><u>' . getTranslation('current_scheduled_bosses') . ':</u></b>';
    foreach($query->fetchAll() as $result) {
      if($prev_start != $result['date_start']) {
        $msg.= CR . EMOJI_CLOCK . ' <b>' . $result['date_start'] . '  —  ' . $result['date_end'] . ':</b>' . CR;
        $prev_rl = '';
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
          'callback_data' => 'pokedex_import'
        ]
      ]
    ];
  }else {
    $msg .= getTranslation('upcoming_bosses_not_found');
    $keys = [
      [
        [
          'text' => getTranslation('done'),
          'callback_data' => formatCallbackData(['exit', 'd' => '1'])
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

exit();
