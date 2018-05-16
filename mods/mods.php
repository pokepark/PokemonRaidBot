<?php
// Write to log.
debug_log('mods()');

// For debug.
//debug_log($update);
//debug_log($data);

// Get the limit.
$limit = $data['id'];

// Get the action.
$action = $data['arg'];

if ($update['callback_query']['message']['chat']['type'] == 'private') {
    // List moderators.
    if ($action == "list") {
	// Set message.
	$msg = getTranslation('mods_list_of_all') . CR . getTranslation('mods_details');
        // Get moderators.
        $keys = edit_moderator_keys($limit, $action);

    // Add modertor.
    } else if ($action == "add" ) {
	// Set message.
	$msg = getTranslation('mods_add_new');
	// Get users.
        $keys = edit_moderator_keys($limit, $action);

    // Delete moderator.
    } else if ($action == "delete" ) {
	// Set message.
	$msg = getTranslation('mods_delete');
	// Get users.
        $keys = edit_moderator_keys($limit, $action);
    }

    // Empty keys?
    if (!$keys) {
	$msg = getTranslation('mods_not_found');
    }

    // Edit message.
    edit_message($update, $msg, $keys, false);

    // Build callback message string.
    $callback_response = 'OK';

    // Answer callback.
    answerCallbackQuery($update['callback_query']['id'], $callback_response);
} 

exit();
