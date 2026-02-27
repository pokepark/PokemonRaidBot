<?php
require_once(LOGIC_PATH . '/gymMenu.php');
// Write to log.
debug_log('GYM');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
$botUser->accessCheck('gym-details');

// Set keys.
$gymarea = resolveDefaultGymarea($botUser->userId);
$keys_and_gymarea = gymMenu('gym', false, 1, false, $gymarea);
$keys = $keys_and_gymarea['keys'];

// Set message.
$msg = '<b>' . getTranslation('show_gym_details') . '</b>' . CR . CR;
$msg.= $keys_and_gymarea['gymareaTitle'];

// Send message.
send_message(create_chat_object([$update['message']['chat']['id']]), $msg, $keys, ['reply_markup' => ['selective' => true, 'one_time_keyboard' => true], 'disable_web_page_preview' => 'true']);
