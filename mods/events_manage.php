<?php
// Write to log.
debug_log('EVENTS()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
$botUser->accessCheck($update, 'event-manage');

$columnSettings = [
  'vote_key_mode'     => ['allowed' => [0,1],   'default' => 0, 'nullable' => false],
  'hide_raid_picture' => ['allowed' => [0,1],   'default' => 0, 'nullable' => false],
  'pokemon_title'     => ['allowed' => [0,1,2], 'default' => 1, 'nullable' => false],
  'time_slots'        => ['nullable' => true],
  'raid_duration'     => ['nullable' => false],
  'poll_template'     => ['nullable' => true],
];

$eventId = $data['id'] ?? false;
$arg = $data['arg'] ?? false;
$subArg = ($arg !== false) ? explode('-', $arg) : [];
$keys = [];
$callback_response = 'OK';
$userId = $update['callback_query']['from']['id'] ?? $update['message']['from']['id'];

// Process user input
if(isset($modifiers) && isset($modifiers['action'])) {
  $value = htmlspecialchars(trim($update['message']['text']));
  if($modifiers['action'] == 1) {
    // User input is new event name
    $column = 'name';
    $arg = 0;
  }else if($modifiers['action'] == 2) {
    // User input is new description
    $column = 'description';
    $arg = 0;
  }else if($modifiers['action'] == 3) {
    // User input is raid poll settings
    $column = $modifiers['column'];
    // Validate input
    if($columnSettings[$column]['nullable'] && strtolower($value) == 'null') {
      $value = NULL;
    }
    if(in_array($column, ['vote_key_mode','time_slots','raid_duration','hide_raid_picture','pokemon_title']) && $value != NULL) {
      $value = preg_replace('/\D/', '', $value);
      if(isset($columnSettings[$column]['allowed']) && !in_array($value, $columnSettings[$column]['allowed'])) $value = $columnSettings[$column]['default'];
    }elseif($column == 'poll_template' && $value != NULL) {
      $rows = preg_split("/\r\n|\n|\r/", $value);
      $inputArray = [];
      $i = 0;
      // Convert input into json array
      foreach($rows as $row) {
        $buttons = explode(',', $row);
        foreach($buttons as $button) {
          $button = trim($button);
          if(in_array($button, ['alone', 'extra', 'extra_alien', 'remote', 'inv_plz', 'can_inv', 'ex_inv', 'teamlvl', 'time', 'pokemon', 'refresh', 'alarm', 'here', 'late', 'done', 'cancel'])) {
            $inputArray[$i][] = $button;
          }
        }
        $i++;
      }
      $value = (strtolower($value) == 'null' ? NULL : json_encode($inputArray));
    }
    $arg = 3;
  }
  $eventId = $modifiers['eventId'];
  my_query('UPDATE events SET ' . $column . ' = ? WHERE id=?', [$value, $eventId]);
  $callback_response = getTranslation('done');
  my_query('DELETE FROM user_input WHERE user_id=?', [$userId]);
  editMessageText($modifiers['old_message_id'], getTranslation('updated'), [], $userId);
}

$q = my_query('SELECT * FROM events where id = ?', [$eventId]);
$event = $q->fetch();
if($eventId == EVENT_ID_EX) {
  $event['name'] = getTranslation('Xstars');
}
if(empty($event['description'])) $event['description'] = '<i>' . getTranslation('events_no_description') . '</i>';

$msg = '<b>' . getTranslation('events_manage') . '</b>' . CR . CR;
$msg .= '<u>' . $event['name'] . '</u>' . CR;
$msg .= $event['description'] . CR . CR;

if($arg == 0 || $arg == 'a') {
  if($arg == 'a') {
    my_query('DELETE FROM user_input WHERE user_id=?', [$userId]);
    $callback_response = getTranslation('action_aborted');
  }
  if($eventId != EVENT_ID_EX)
    $keys = universal_key($keys, $eventId, 'events_manage', '1', getTranslation('events_edit_name'));
  $keys = universal_key($keys, $eventId, 'events_manage', '2', getTranslation('events_edit_description'));
  $keys = universal_key($keys, $eventId, 'events_manage', '3', getTranslation('events_edit_raid_poll'));
  if($eventId != EVENT_ID_EX)
    $keys = universal_key($keys, $eventId, 'events_manage', '4', getTranslation('events_delete'));

  $keys[] = [
    universal_inner_key($keys, '0', 'events', '0', getTranslation('back')),
    universal_inner_key($keys, '0', 'exit', '0', getTranslation('done'))
  ];

// Edit event name
}else if($arg == 1) {
  $modifiers = json_encode(['old_message_id'=>$update['callback_query']['message']['message_id'],'action'=>1,'eventId'=>$eventId]);
  my_query('INSERT INTO user_input SET user_id=?, handler=\'events_manage\', modifiers=?', [$userId, $modifiers]);

  $msg .= '<u>' . getTranslation('events_edit_name') . '</u>' . CR;
  $msg .= getTranslation('events_give_name') . ':' . CR;
  $keys = universal_key($keys, $eventId, 'events_manage', 'a', getTranslation('abort'));

// Edit event description
}else if($arg == 2) {
  $modifiers = json_encode(['old_message_id'=>$update['callback_query']['message']['message_id'],'action'=>2,'eventId'=>$eventId]);
  my_query('INSERT INTO user_input SET user_id=?, handler=\'events_manage\', modifiers=?', [$userId, $modifiers]);
  $msg .= '<u>' . getTranslation('events_edit_description') . '</u>' . CR;
  $msg .= getTranslation('events_give_description') . ':';
  $keys = universal_key($keys, $eventId, 'events_manage', 'a', getTranslation('abort'));

// Edt event raid poll settings
}else if($arg == 3) {
  my_query('DELETE FROM user_input WHERE user_id=?', [$userId]);
  $templateArray = ($event['poll_template'] == NULL) ? $config->RAID_POLL_UI_TEMPLATE : json_decode($event['poll_template'], true); 
  $event['poll_template'] = templateJsonToString($templateArray);
  $printColumns = ['vote_key_mode','time_slots','raid_duration','hide_raid_picture','pokemon_title','poll_template'];

  $msg .= 'https://pokemonraidbot.readthedocs.io/en/latest/config.html#event-raids' . CR;
  $msg .= 'https://pokemonraidbot.readthedocs.io/en/latest/config.html#raid-poll-design-and-layout' . CR . CR;
  foreach($printColumns as $column) {
    $msg .= $column . ': ';
    $msg .= ($column == 'poll_template' ? CR : '');
    $msg .= '<code>' . ($event[$column] === NULL ? 'NULL' : $event[$column]) . '</code>' . CR;
    $keys = universal_key($keys, $eventId, 'events_manage', 'e-'.$column, $column);
  }
  $keys[] = [
    universal_inner_key($keys, $eventId, 'events_manage', '0', getTranslation('back')),
    universal_inner_key($keys, '0', 'exit', '1', getTranslation('done'))
  ];

// Delete event confirmation
}else if($arg == 4) {
  $msg .= '<b>' . getTranslation('events_delete_confirmation') . '</b>' . CR;
  $keys[] = [
    universal_inner_key($keys, $eventId, 'events_manage', 'd', getTranslation('yes')),
    universal_inner_key($keys, $eventId, 'events_manage', '0', getTranslation('no'))
  ];

// Delete event
}else if($arg == 'd') {
  if($eventId != EVENT_ID_EX) my_query('DELETE FROM events WHERE id=?', [$eventId]);
  $data['id'] = $data['arg'] = 0;
  include(ROOT_PATH . '/mods/events.php');
  exit;

// Prompt for raid poll value editing
}else if($subArg[0] == 'e') {
  $valueToEdit = $subArg[1];
  $modifiers = json_encode(['old_message_id'=>$update['callback_query']['message']['message_id'],'action'=>3,'column'=>$valueToEdit,'eventId'=>$eventId]);
  my_query('INSERT INTO user_input SET user_id=?, handler=\'events_manage\', modifiers=?', [$userId, $modifiers]);

  if($valueToEdit == 'poll_template') {
    $templateArray = ($event['poll_template'] == NULL) ? $config->RAID_POLL_UI_TEMPLATE : json_decode($event['poll_template'], true); 
    $event['poll_template'] = templateJsonToString($templateArray);
  }

  $msg .= $valueToEdit . CR;
  $msg .= getTranslation('old_value') . CR;
  $msg .= '<code>' . ($event[$valueToEdit] === NULL ? 'NULL' : $event[$valueToEdit]) . '</code>' . CR . CR;
  $msg .= getTranslation('new_value');
  $keys = universal_key($keys, $eventId, 'events_manage', '3', getTranslation('back'));

}

$tg_json = [];

if(isset($update['callback_query'])) {
  // Answer callback.
  $tg_json[] = answerCallbackQuery($update['callback_query']['id'], $callback_response, true);
  // Edit the message.
  $tg_json[] = edit_message($update, $msg, $keys, false, true);
}else {
  $tg_json[] = send_message($update['message']['chat']['id'], $msg, $keys, false, true);
}

// Telegram multicurl request.
curl_json_multi_request($tg_json);

function templateJsonToString($templateArray) {
  $templateString = '';
  foreach($templateArray as $line) {
    foreach($line as $button) {
      $templateString .= $button;
      $templateString .= ',';
    }
    $templateString = rtrim($templateString, ',') . CR;
  }
  return $templateString;
}
// Exit.
exit();
