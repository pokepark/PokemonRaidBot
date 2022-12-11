<?php
// Write to log.
debug_log('EVENTS()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
$botUser->accessCheck('event-manage');

$keys = [];
$callback_response = 'OK';

// Manage events
$q = my_query('SELECT * FROM events');
$msg = '<b>' . getTranslation('events_manage') . '</b>' . CR;
foreach($q->fetchAll() as $event) {
  if($event['id'] == EVENT_ID_EX) $event['name'] = getTranslation('Xstars');
  if(empty($event['description'])) $event['description'] = '<i>' . getTranslation('events_no_description') . '</i>';
  $msg .= '<u>' . $event['name'] . '</u>' . CR;
  $msg .= $event['description'] . CR . CR;
  $keys[] = [
    [
      'text' => $event['name'],
      'callback_data' => $event['id'] . ':events_manage:0',
    ]
  ];
}
$keys[] = [
  [
    'text' => getTranslation('done'),
    'callback_data' => formatCallbackData(['exit', 'd' => '1']),
  ]
];

$tg_json = [];

// Answer callback.
$tg_json[] = answerCallbackQuery($update['callback_query']['id'], $callback_response, true);

// Edit the message.
$tg_json[] = edit_message($update, $msg, $keys, false, true);

// Telegram multicurl request.
curl_json_multi_request($tg_json);
