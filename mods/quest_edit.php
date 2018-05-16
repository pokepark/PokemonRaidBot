<?php
// Write to log.
debug_log('quest_edit()');

// For debug.
//debug_log($update);
//debug_log($data);

// Quest id.
$quest_id = $data['id'];

// Set the user id.
$userid = $update['callback_query']['from']['id'];

// Init keys.
$keys = array();
$keys_share = array();
$keys_delete = array();

// Add keys to delete and share.
$keys_delete = universal_key($keys, $quest_id, 'quest_delete', '0', getTranslation('delete'));
$keys_share = share_quest_keys($quest_id, $userid);
$keys = array_merge($keys_delete, $keys_share);

// Add abort navigation key.
$nav_keys = array();
$nav_keys[] = universal_inner_key($keys, '0', 'exit', '0', getTranslation('abort'));
$keys[] = $nav_keys;

// Set message.
$msg = '<b>' . getTranslation('quest') . ':</b>' . CR . CR;
$quest = get_quest($quest_id);
$msg .= get_formatted_quest($quest);

// Edit message.
edit_message($update, $msg, $keys, ['disable_web_page_preview' => 'true']);

// Build callback message string.
$callback_response = 'OK';

// Answer callback.
answerCallbackQuery($update['callback_query']['id'], $callback_response);

exit();
