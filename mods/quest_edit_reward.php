<?php
// Write to log.
debug_log('quest_edit_reward()');

// For debug.
//debug_log($update);
//debug_log($data);

// Pokestop id.
$pokestop_id = $data['id'];

// Questlist id and type.
$quest_id_type = explode(",", $data['arg']);
$quest_id = $quest_id_type[0];
$quest_type = $quest_id_type[1];

// Get pokestop and questlist data.
$stop = get_pokestop($pokestop_id, false);
$ql_entry = get_questlist_entry($quest_id);

// Quest action: Singular or plural?
$quest_action = explode(":", getTranslation('quest_action_' . $ql_entry['quest_action']));
$quest_action_singular = $quest_action[0];
$quest_action_plural = $quest_action[1];
$qty_action = $ql_entry['quest_quantity'] . SP . (($ql_entry['quest_quantity'] > 1) ? ($quest_action_plural) : ($quest_action_singular));

// Build message string.
$msg = '';
$msg .= getTranslation('pokestop') . ': <b>' . $stop['pokestop_name'] . '</b>' . (!empty($stop['address']) ? (CR . $stop['address']) : '');
$msg .= CR . getTranslation('quest') . ': <b>' . getTranslation('quest_type_' . $quest_type) . SP . $qty_action . '</b>';
$msg .= CR . CR . '<b>' . getTranslation('reward_select_type') . '</b>';

// Create the keys.
$keys = reward_type_keys($pokestop_id, $quest_id, $quest_type);

// Edit message.
edit_message($update, $msg, $keys, false);

// Build callback message string.
$callback_response = 'OK';

// Answer callback.
answerCallbackQuery($update['callback_query']['id'], $callback_response);

exit();
