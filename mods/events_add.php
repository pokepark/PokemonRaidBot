<?php
// Write to log.
debug_log('EVENTS()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
$botUser->accessCheck($update, 'event-manage');

$keys = [];
$callback_response = 'OK';
$userId = $update['callback_query']['from']['id'] ?? $update['message']['from']['id'];

if(isset($modifiers)) {
  $value = htmlspecialchars(trim($update['message']['text']));
  my_query('INSERT INTO events SET name=?',[$value]);
  $eventId = $dbh->lastInsertId();
  my_query('DELETE FROM user_input WHERE user_id=?', [$userId]);
  $callback_response = getTranslation('done');
  editMessageText($modifiers['old_message_id'], getTranslation('events_created'), [], $userId);
  $msg = '<b>' . getTranslation('events_created') . '</b>' . CR;
  $msg .= $value;
  $keys[] = [
    [
      'text' => getTranslation('next'),
      'callback_data' => $eventId . ':events_manage:0',
    ]
  ];
}else {
  if($data['arg'] == 0) {
    // Add a new event
    $msg = '<b>' . getTranslation('events_create') . '</b>' . CR;
    $msg .= getTranslation('events_give_name') . ':';

    $modifiers = json_encode(['old_message_id'=>$update['callback_query']['message']['message_id']]);
    $userId = $update['callback_query']['from']['id'];

    // Data for handling response from the user
    my_query('INSERT INTO user_input SET user_id=?, handler=\'events_add\', modifiers=?', [$userId, $modifiers]);

    $keys[] = [
      [
        'text' => getTranslation('abort'),
        'callback_data' => '0:events_add:a',
      ]
    ];
  }elseif($data['arg'] == 'a') {
    my_query('DELETE FROM user_input WHERE user_id=?', [$userId]);
    answerCallbackQuery($update['callback_query']['id'], 'OK');
    editMessageText($update['callback_query']['message']['message_id'], getTranslation('action_aborted'), [], $userId);
    exit;
  }
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

// Exit.
exit();
