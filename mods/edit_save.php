<?php
// Write to log.
debug_log('edit_save()');
require_once(LOGIC_PATH . '/show_raid_poll_small.php');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
$botUser->accessCheck('create');

// Set raid id
$id = $data['r'];

// Set the user id.
$userid = $update['callback_query']['from']['id'];

// Update only if time is not equal to RAID_DURATION
if($data['d'] != $config->RAID_DURATION) {

  // Build query.
  my_query('
    UPDATE  raids
    SET     end_time = DATE_ADD(start_time, INTERVAL ' . $data['d'] . ' MINUTE)
      WHERE   id = :id
    ', ['id' => $id]
  );
}

// Telegram JSON array.
$tg_json = array();

// Add delete to keys.
$keys = [
  [
    [
      'text'          => getTranslation('delete'),
      'callback_data' => $id . ':raids_delete:0'
    ]
  ]
];

// Check access level prior allowing to change raid time
if($botUser->accessCheck('raid-duration', true)) {
  // Add time change to keys.
  $keys[] = [
    [
      'text'          => getTranslation('change_raid_duration'),
      'callback_data' => formatCallbackData(['callbackAction' => 'edit_time', 'r' => $id, 'o' => 'm'])
    ]
  ];
}

// Get raid times.
$raid = get_raid($id);

// Get raid level.
$raid_level = $raid['level'];

if($raid['event'] !== NULL) {
  $event_button_text = ($raid['event_note'] == NULL) ? getTranslation("event_note_add") : getTranslation("event_note_edit");
  $keys[] = [
    [
      'text'          => $event_button_text,
      'callback_data' => $id . ':edit_event_note:0'
    ]
  ];
}

// Add keys to share.
$keys_share = share_keys($id, 'raid_share', $update, $raid_level);
$keys = array_merge($keys, $keys_share);

// Build message string.
$msg = '';
$msg .= getTranslation('raid_saved') . CR;
$msg .= show_raid_poll_small($raid, false) . CR;

// User_id tag.
$user_id_tag = '#' . $update['callback_query']['from']['id'];

// Gym Name
if(!empty($raid['gym_name']) && ($raid['gym_name'] == $user_id_tag)) {
  $msg .= getTranslation('set_gym_name_and_team') . CR2;
  $msg .= getTranslation('set_gym_name_command') . CR;
}

// Build callback message string.
$callback_response = 'OK';

// Answer callback.
$tg_json[] = answerCallbackQuery($update['callback_query']['id'], $callback_response, true);

// Edit message.
$tg_json[] = edit_message($update, $msg, $keys, false, true);

// Telegram multicurl request.
curl_json_multi_request($tg_json);
