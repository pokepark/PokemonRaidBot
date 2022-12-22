<?php
// Write to log.
debug_log('EVENTS()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
$botUser->accessCheck('event-manage');

$q = my_query('SELECT * FROM events');

$msg = '<b>' . getTranslation('events_manage') . '</b>' . CR;

foreach($q->fetchAll() as $event) {
  if($event['id'] == EVENT_ID_EX) $event['name'] = getTranslation('Xstars');
  if(empty($event['description'])) $event['description'] = '<i>' . getTranslation('events_no_description') . '</i>';
  $msg .= '<u>' . $event['name'] . '</u>' . CR;
  $msg .= $event['description'] . CR . CR;
}

$keys[][] = button(getTranslation('events_manage'), 'events');
$keys[][] = button(getTranslation('events_create'), 'events_add');
$keys[][] = button(getTranslation('done'), ['exit', 'd' => '1']);

// Send message.
send_message($update['message']['chat']['id'], $msg, $keys, ['reply_markup' => ['selective' => true, 'one_time_keyboard' => true]]);
