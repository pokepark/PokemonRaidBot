<?php
// Write to log
debug_log("Saving event note:");
require_once(LOGIC_PATH . '/show_raid_poll_small.php');

// Get wanted info from modifiers in db
$raid_id = $modifiers['id'];
// Set the user_id
$user_id = $update['message']['from']['id'];

// Update the event note to raid table
my_query('UPDATE raids SET event_note=:text WHERE id = :id', [':text' => $update['message']['text'], ':id' => $raid_id]);

// Remove back button from previous message to avoid confusion
edit_message_keyboard($modifiers['old_message_id'], [], $user_id);

// Return message to user
$raid = get_raid($raid_id);
$msg = '';
$msg .= getTranslation('raid_saved') . CR;
$msg .= CR.getTranslation('event_note').': '.$update['message']['text'].CR2;
$msg .= show_raid_poll_small($raid) . CR;
debug_log($msg);

$keys[][] = button(getTranslation('event_note_edit'), ['edit_event_note', 'r' => $raid_id, 'm' => 'e']);
$keys[][] = button(getTranslation('delete'), ['raids_delete', 'r' => $raid_id]);
$keys_share = share_keys($raid_id, 'raid_share', $update, $raid['level']);
$keys = array_merge($keys, $keys_share);
debug_log($keys);

// Send response message to user
send_message(create_chat_object([$user_id]), $msg, $keys, []);
