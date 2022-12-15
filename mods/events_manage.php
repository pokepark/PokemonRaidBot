<?php
// Write to log.
debug_log('EVENTS()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
$botUser->accessCheck('event-manage');

$columnSettings = [
  'vote_key_mode'     => ['allowed' => [0,1],   'default' => 0, 'nullable' => false],
  'hide_raid_picture' => ['allowed' => [0,1],   'default' => 0, 'nullable' => false],
  'pokemon_title'     => ['allowed' => [0,1,2], 'default' => 1, 'nullable' => false],
  'time_slots'        => ['nullable' => true],
  'raid_duration'     => ['nullable' => false],
  'poll_template'     => ['nullable' => true],
];

$eventId = $data['e'] ?? false;
$action = $data['a'] ?? false;
$keys = [];
$callback_response = 'OK';
$userId = $update['callback_query']['from']['id'] ?? $update['message']['from']['id'];

// Process user input
if(isset($modifiers) && isset($modifiers['action'])) {
  $value = htmlspecialchars(trim($update['message']['text']));
  if($modifiers['action'] == 1) {
    // User input is new event name
    $column = 'name';
    $action = 0;
  }else if($modifiers['action'] == 2) {
    // User input is new description
    $column = 'description';
    $action = 0;
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
    $action = 3;
  }
  $eventId = $modifiers['eventId'];
  my_query('UPDATE events SET ' . $column . ' = ? WHERE id=?', [$value, $eventId]);
  $callback_response = getTranslation('done');
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

if($action == 0 || $action == 'a') {
  if($action == 'a') {
    my_query('DELETE FROM user_input WHERE user_id=?', [$userId]);
    $callback_response = getTranslation('action_aborted');
  }
  if($eventId != EVENT_ID_EX)
    $keys[][] = button(getTranslation('events_edit_name'), ['events_manage', 'e' => $eventId, 'a' => 1]);
  $keys[][] = button(getTranslation('events_edit_description'), ['events_manage', 'e' => $eventId, 'a' => 2]);
  $keys[][] = button(getTranslation('events_edit_raid_poll'), ['events_manage', 'e' => $eventId, 'a' => 3]);
  if($eventId != EVENT_ID_EX)
    $keys[][] = button(getTranslation('events_delete'), ['events_manage', 'e' => $eventId, 'a' => 4]);

  $keys[] = [
    button(getTranslation('back'), 'events'),
    button(getTranslation('done'), 'exit')
  ];

// Edit event name
}else if($action == 1) {
  $modifiers = json_encode(['old_message_id'=>$update['callback_query']['message']['message_id'],'action'=>1,'eventId'=>$eventId]);
  my_query('INSERT INTO user_input SET user_id=?, handler=\'events_manage\', modifiers=?', [$userId, $modifiers]);

  $msg .= '<u>' . getTranslation('events_edit_name') . '</u>' . CR;
  $msg .= getTranslation('events_give_name') . ':' . CR;
  $keys[][] = button(getTranslation('abort'), ['events_manage', 'e' => $eventId, 'a' => 'a']);

// Edit event description
}else if($action == 2) {
  $modifiers = json_encode(['old_message_id'=>$update['callback_query']['message']['message_id'],'action'=>2,'eventId'=>$eventId]);
  my_query('INSERT INTO user_input SET user_id=?, handler=\'events_manage\', modifiers=?', [$userId, $modifiers]);
  $msg .= '<u>' . getTranslation('events_edit_description') . '</u>' . CR;
  $msg .= getTranslation('events_give_description') . ':';
  $keys[][] = button(getTranslation('abort'), ['events_manage', 'e' => $eventId, 'a' => 'a']);

// Edt event raid poll settings
}else if($action == 3) {
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
    $keys[][] = button($column, ['events_manage', 'e' => $eventId, 'a' => 'e', 'c' => $column]);
  }
  $keys[] = [
    button(getTranslation('back'), ['events_manage', 'e' => $eventId, 'a' => 0]),
    button(getTranslation('done'), ['exit', 'd' => 1]),
  ];

// Delete event confirmation
}else if($action == 4) {
  $msg .= '<b>' . getTranslation('events_delete_confirmation') . '</b>' . CR;
  $keys[] = [
    button(getTranslation('yes'), ['events_manage', 'e' => $eventId, 'a' => 'd']),
    button(getTranslation('no'), ['events_manage', 'e' => $eventId, 'a' => 0]),
  ];

// Delete event
}else if($action == 'd') {
  if($eventId != EVENT_ID_EX) my_query('DELETE FROM events WHERE id=?', [$eventId]);
  include(ROOT_PATH . '/mods/events.php');
  exit;

// Prompt for raid poll value editing
}else if($action == 'e') {
  $valueToEdit = $data['c'];
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
  $keys[][] = button(getTranslation('back'), ['events_manage', 'e' => $eventId, 'a' => 3]);

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
      $templateString .= $button . ',';
    }
    $templateString = rtrim($templateString, ',') . CR;
  }
  return $templateString;
}
