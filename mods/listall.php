<?php
// Write to log.
debug_log('LIST');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
bot_access_check($update, 'list');

// Get the keys.
$keys_and_gymarea = raid_edit_gyms_first_letter_keys('list_by_gym',false, $data['id'], 'listall');
$keys = $keys_and_gymarea['keys'];

// Telegram JSON array.
$tg_json = array();

// Build callback message string.
$callback_response = getTranslation('select_gym');

// Answer callback.
$tg_json[] = answerCallbackQuery($update['callback_query']['id'], $callback_response, true);

// Edit the message.
$msg = '<b>' . getTranslation('list_all_active_raids') . '</b>' . CR;
$msg .= '<b>' . getTranslation('select_gym_first_letter') . '</b>' . CR;
$msg.= (($keys_and_gymarea['gymarea_name'] != '') ? CR . CR . getTranslation('current_gymarea') . ': ' . $keys_and_gymarea['gymarea_name'] : '');
$tg_json[] = edit_message($update, $msg, $keys, false, true);

// Telegram multicurl request.
curl_json_multi_request($tg_json);

// Exit.
exit();
