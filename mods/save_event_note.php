<?php
// Write to log
debug_log("Saving event note:");

// Get wanted info from modifiers in db
$raid_id = $modifiers['id'];

// Update the event note to raid table
$query = $dbh->prepare("UPDATE raids SET event_note=:text WHERE id = :id");
$query->execute([':text' => $update['message']['text'], ':id' => $raid_id]);

// Return message to user
$msg = '';
$msg .= getTranslation('raid_saved') . CR;
$msg .= CR.getTranslation('event_note').': '.$update['message']['text'].CR2;
$msg .= show_raid_poll_small(get_raid($raid_id)) . CR;
debug_log($msg);

$keys = [
    [
        [
            'text'          => getTranslation('event_note_edit'),
            'callback_data' => $raid_id . ':edit_event_note:edit'
        ]
    ],
    [
        [
            'text'          => getTranslation('delete'),
            'callback_data' => $raid_id . ':raids_delete:0'
        ]
    ]
];
$keys_share = share_keys($raid_id, 'raid_share', $update, $chats);
$keys = array_merge($keys, $keys_share);
debug_log($keys);

// Send response message to user
send_message($update['message']['from']['id'],$msg,$keys,[]);
