<?php
// Write to log.
debug_log('raids_list()');
require_once(LOGIC_PATH . '/show_raid_poll_small.php');

// For debug.
//debug_log($update);
//debug_log($data);

// Get ID.
$raidId = $data['r'];

// Check access.
$botUser->accessCheck('list');

// Get raid details.
$raid = get_raid($raidId);

// Create keys array.
$keys = [];
// Probably unused feature. Will fix if someone needs this
// $keys[][] = button(getTranslation('expand'), ['vote_refresh', 'r' => $raid['id']]);
if($botUser->raidaccessCheck($raidId, 'pokemon', true)) {
  $keys[][] = button(getTranslation('update_pokemon'), ['raid_edit_poke', 'r' => $raid['id'], 'rl' => $raid['level']]);
}
if($botUser->raidaccessCheck($raidId, 'delete', true)) {
  $keys[][] = button(getTranslation('delete'), ['raids_delete', 'r' => $raid['id']]);
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
$keys[][] = button(getTranslation('done'), 'exit');

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
