<?php
// Write to log.
debug_log('raids_list()');
require_once(LOGIC_PATH . '/show_raid_poll_small.php');

// For debug.
//debug_log($update);
//debug_log($data);

// Get ID.
$raidId = $data['id'];

// Check access.
$botUser->accessCheck($update, 'list');

// Get raid details.
$raid = get_raid($raidId);

// Create keys array.
$keys = [
  [
    [
      'text'          => getTranslation('expand'),
      'callback_data' => $raid['id'] . ':vote_refresh:0',
    ]
  ]
];
if($botUser->raidAccessCheck($update, $raidId, 'pokemon', true)) {
  $keys[] = [
      [
        'text'          => getTranslation('update_pokemon'),
        'callback_data' => $raid['id'] . ':raid_edit_poke:' . $raid['level'],
      ]
  ];
}
if($botUser->raidAccessCheck($update, $raidId, 'delete', true)) {
  $keys[] = [
      [
        'text'          => getTranslation('delete'),
        'callback_data' => $raid['id'] . ':raids_delete:0'
      ]
  ];
}

// Add keys to share.
debug_log($raid, 'raw raid data for share: ');
$keys_share = share_keys($raid['id'], 'raid_share', $update, $raid['level']);
if(!empty($keys_share)) {
  $keys = array_merge($keys, $keys_share);
} else {
  debug_log('There are no groups to share to, is SHARE_CHATS set?');
}
// Exit key
$keys = universal_key($keys, '0', 'exit', '1', getTranslation('done'));

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
