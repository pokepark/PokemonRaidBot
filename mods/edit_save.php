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

// Update raid end time
if(isset($data['d'])) {
  my_query('
    UPDATE  raids
    SET     end_time = DATE_ADD(start_time, INTERVAL :duration MINUTE)
      WHERE   id = :id
    ', [
      'id' => $id,
      'duration' => $data['d']
    ]
  );
}

// Telegram JSON array.
$tg_json = array();

// Add delete and done to keys.
$keys[][] = button(getTranslation('delete'), ['raids_delete', 'r' => $id]);
$keys[][] = button(getTranslation('done'), ['exit', 'd' => 1]);

// Check access level prior allowing to change raid time
if($botUser->accessCheck('raid-duration', true)) {
  // Add time change to keys.
  $keys[][] = button(getTranslation('change_raid_duration'), ['edit_time', 'r' => $id, 'o' => 'm']);
}

// Get raid times.
$raid = get_raid($id);

// Get raid level.
$raid_level = $raid['level'];

if($raid['event'] !== NULL) {
  $event_button_text = ($raid['event_note'] == NULL) ? getTranslation("event_note_add") : getTranslation("event_note_edit");
  $keys[][] = button($event_button_text, ['edit_event_note', 'r' => $id, 'm' => 'e']);
}

// Add keys to share.
$keys_share = share_keys($id, 'raid_share', $update, $raid_level);
$keys = array_merge($keys, $keys_share);

// Build message string.
$msg = '';
$msg .= getTranslation('raid_saved') . CR;
$msg .= show_raid_poll_small($raid) . CR;

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
