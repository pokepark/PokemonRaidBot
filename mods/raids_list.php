<?php
// Write to log.
debug_log('raids_list()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
bot_access_check($update, 'list');

// Get ID.
$id = $data['id'];

// Get raid details.
$raid = get_raid($id);

// Create keys array.
$keys = [
    [
        [
            'text'          => getTranslation('expand'),
            'callback_data' => $raid['id'] . ':vote_refresh:0',
        ]
    ],
    [
        [
            'text'          => getTranslation('update_pokemon'),
            'callback_data' => $raid['id'] . ':raid_edit_poke:' . $raid['pokemon'],
        ]
    ],
    [
        [
            'text'          => getTranslation('delete'),
            'callback_data' => $raid['id'] . ':raids_delete:0'
        ]
    ]
];

// Add keys to share.
debug_log($raid, 'raw raid data for share: ');
$keys_share = share_keys($raid['id'], 'raid_share', $update, '', '', false, $raid['level']);
if(is_array($keys_share)) {
    $keys = array_merge($keys, $keys_share);
} else {
    debug_log('There are no groups to share to, is SHARE_CHATS set?');
}
// Exit key
$empty_exit_key = [];
$key_exit = universal_key($empty_exit_key, '0', 'exit', '1', getTranslation('done'));
$keys = array_merge($keys, $key_exit);

// Get message.
$msg = show_raid_poll_small($raid);

// Build callback message string.
$callback_response = 'OK';

// Telegram JSON array.
$tg_json = array();

// Answer callback.
$tg_json[] = answerCallbackQuery($update['callback_query']['id'], $callback_response, true);

// Edit message.
$tg_json[] = edit_message($update, $msg, $keys, false, true);

// Telegram multicurl request.
curl_json_multi_request($tg_json);

// Exit.
exit();
