<?php
// Write to log.
debug_log('list_raid()');
require_once(LOGIC_PATH . '/get_raid_times.php');
require_once(LOGIC_PATH . '/show_raid_poll_small.php');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
$botUser->accessCheck('list');

// Get gym ID.
$gym_id = $data['g'] ?? 0;
$raid_id = $data['r'] ?? 0;

// Get raid details.
if($raid_id != 0) {
  $sql_condition = 'AND raids.id = ? LIMIT 1';
  $binds = [$raid_id];
}else {
  $eventQuery = 'event IS NULL';
  if($botUser->accessCheck('ex-raids', true)) {
    if($botUser->accessCheck('event-raids', true))
      $eventQuery = '';
    else
      $eventQuery .= ' OR event = ' . EVENT_ID_EX;
  }elseif($botUser->accessCheck('event-raids', true)) {
    $eventQuery = 'event != ' . EVENT_ID_EX .' OR event IS NULL';
  }
  $eventQuery = ($eventQuery == '') ? ' ' : ' AND ('.$eventQuery.') ';
  $sql_condition = 'AND gyms.id = ? ' . $eventQuery;
  $binds = [$gym_id];
}
$rs = my_query('
  SELECT   raids.id
  FROM     raids
  LEFT JOIN  gyms
  ON     raids.gym_id = gyms.id
  WHERE    end_time > UTC_TIMESTAMP()
  ' . $sql_condition
  ,$binds
);
if($rs->rowcount() == 1) {
  // Get the row.
  $raid_fetch = $rs->fetch();
  $raid = get_raid($raid_fetch['id']);

  debug_log($raid);

  // Create keys array.
  $keys = [
    [
      [
        'text'          => getTranslation('expand'),
        'callback_data' => $raid['id'] . ':vote_refresh:0',
      ]
    ]
  ];
  if($botUser->raidaccessCheck($raid['id'], 'pokemon', true)) {
    $keys[] = [
        [
          'text'          => getTranslation('update_pokemon'),
          'callback_data' => $raid['id'] . ':raid_edit_poke:' . $raid['level'],
        ]
    ];
  }
  if($botUser->raidaccessCheck($raid['id'], 'delete', true)) {
    $keys[] = [
        [
          'text'          => getTranslation('delete'),
          'callback_data' => $raid['id'] . ':raids_delete:0'
        ]
    ];
  }

  // Add keys to share.
  debug_log($raid, 'raw raid data for share: ');
  $keys_share = share_keys($raid['id'], 'raid_share', $update, $raid['level']);
  if(!empty($keys_share)) {
    $keys = array_merge($keys, $keys_share);
  } else {
    debug_log('There are no groups to share to, is SHARE_CHATS set?');
  }
  // Exit key
  $keys = universal_key($keys, '0', 'exit', '1', getTranslation('done'));

  // Get message.
  $msg = show_raid_poll_small($raid);

}else {
  $msg = getTranslation('list_all_active_raids').':'. CR;
  $keys = [];
  $i = 1;
  while($raid_fetch = $rs->fetch()) {
    $raid = get_raid($raid_fetch['id']);
    $raid_pokemon_name = get_local_pokemon_name($raid['pokemon'], $raid['pokemon_form']);
    $msg .= '<b>' . $i .'. ' . $raid_pokemon_name . '</b>' . CR;
    if(!empty($raid['event_name'])) $msg .= $raid['event_name'] . CR;
    $msg .= get_raid_times($raid,false, true) . CR . CR;
    $keys[] = [
      [
        'text'          => $i . '. ' . $raid_pokemon_name,
        'callback_data' => formatCallbackData(['list_raid', 'r' => $raid['id']])
      ]
    ];
    $i++;
  }
  $callback = [
    'gymMenu',
    'stage' => 2,
    'a' => 'list',
    'fl' => $data['fl'],
    'ga' => $data['ga'],
    'h' => $data['h'],
  ];
  $keys[] = [
    [
      'text'          => getTranslation('back'),
      'callback_data' => formatCallbackData($callback)
    ]
  ];
}

// Build callback message string.
$callback_response = 'OK';

// Telegram JSON array.
$tg_json = array();

// Answer callback.
$tg_json[] = answerCallbackQuery($update['callback_query']['id'], $callback_response, true);

// Edit message.
$tg_json[] = edit_message($update, $msg, $keys, false, true);

// Telegram multicurl request.
curl_json_multi_request($tg_json);
