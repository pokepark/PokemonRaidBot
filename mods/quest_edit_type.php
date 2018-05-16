<?php
// Write to log.
debug_log('quest_edit_qty_action()');

// For debug.
//debug_log($update);
//debug_log($data);

// Pokestop id.
$pokestop_id = $data['id'];

// Quest type.
$quest_type = $data['arg'];

// Build message string.
$msg = '';
$stop = get_pokestop($pokestop_id, false);
$msg .= getTranslation('pokestop') . ': <b>' . $stop['pokestop_name'] . '</b>' . (!empty($stop['address']) ? (CR . $stop['address']) : '');
$msg .= CR . getTranslation('quest') . ': <b>' . getTranslation('quest_type_' . $quest_type) . '...</b>';
$msg .= CR . CR . '<b>' . getTranslation('quest_select_qty_action') . '</b>';

// Create the keys.
$keys = quest_qty_action_keys($pokestop_id, $quest_type);

// Edit message.
edit_message($update, $msg, $keys, false);

// Build callback message string.
$callback_response = 'OK';

// Answer callback.
answerCallbackQuery($update['callback_query']['id'], $callback_response);

exit();
