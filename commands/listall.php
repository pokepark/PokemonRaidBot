<?php
require_once(LOGIC_PATH . '/gymMenu.php');
// Write to log.
debug_log('LIST');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
if(!isset($skip_access) or $skip_access != true) $botUser->accessCheck('listall');

// Set keys.
$keys_and_gymarea = gymMenu('list', false, 1, false, $config->DEFAULT_GYM_AREA);
$keys = $keys_and_gymarea['keys'];

// Set message.
$msg = '<b>' . getTranslation('list_all_active_raids') . '</b>' . CR;
$msg.= $keys_and_gymarea['gymareaTitle'];

// Send message.
send_message($update['message']['chat']['id'], $msg, $keys, ['reply_markup' => ['selective' => true, 'one_time_keyboard' => true], 'disable_web_page_preview' => 'true']);
