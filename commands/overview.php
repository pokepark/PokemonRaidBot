<?php
// Write to log.
debug_log('OVERVIEW()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
$botUser->accessCheck('overview');

// Create keys array.
$keys[][] = button(getTranslation('overview_share'), 'overview_share');
$keys[][] = button(getTranslation('overview_delete'), 'overview_delete');
$keys[][] = button(getTranslation('abort'), ['exit', 'd' => '0']);

// Set message.
$msg = '<b>' . getTranslation('raids_share_overview') . ':</b>';

// Send message.
send_message(create_chat_object([$update['message']['chat']['id']]), $msg, $keys, ['reply_markup' => ['selective' => true, 'one_time_keyboard' => true]]);
