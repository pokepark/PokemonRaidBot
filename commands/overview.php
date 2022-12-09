<?php
// Write to log.
debug_log('OVERVIEW()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
$botUser->accessCheck('overview');

// Create keys array.
$keys = [
  [
    [
      'text'          => getTranslation('overview_share'),
      'callback_data' => 'overview_share'
    ],
    [
      'text'          => getTranslation('overview_delete'),
      'callback_data' => 'overview_delete'
    ]
  ]
];

// Set message.
$msg = '<b>' . getTranslation('raids_share_overview') . ':</b>';

// Send message.
send_message($update['message']['chat']['id'], $msg, $keys, ['reply_markup' => ['selective' => true, 'one_time_keyboard' => true]]);
