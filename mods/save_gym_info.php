<?php
// Write to log
debug_log("Saving event note:");
require_once(LOGIC_PATH . '/edit_gym_keys.php');
require_once(LOGIC_PATH . '/get_gym_details.php');
require_once(LOGIC_PATH . '/get_gym.php');

// Set the user_id
$user_id = $update['message']['from']['id'];

$action = $modifiers['value'];
$gym_id = $modifiers['id'];
$gym = get_gym($gym_id);

$input = trim($update['message']['text']);
$query = false;
$keys = [];
if($action == 'gps') {
  $reg_exp_coordinates = '^[-+]?([1-8]?\d(\.\d+)?|90(\.0+)?),\s*[-+]?(180(\.0+)?|((1[0-7]\d)|([1-9]?\d))(\.\d+)?)$^';
  if(!preg_match($reg_exp_coordinates, $input)) {
    send_message($user_id, getTranslation("gym_gps_coordinates_format_error") . CR . getTranslation("gym_gps_example"));
    exit();
  }
  [$lat, $lon] = explode(',', $input, 2);
  $query = 'UPDATE gyms SET lat = :lat, lon = :lon WHERE id = :id';
  $binds = [
    ':lat' => $lat,
    ':lon' => $lon,
    ':id' => $gym_id,
  ];
  $gym['lat'] = $lat;
  $gym['lon'] = $lon;
}else if(in_array($action, ['addr','name','note'])) {
  if(strlen($input) > 255) {
    send_message($user_id, getTranslation('gym_edit_text_too_long'));
    exit();
  }
  $column_map = ['addr' => 'address', 'name' => 'gym_name', 'note' => 'gym_note'];
  $query = 'UPDATE gyms SET ' . $column_map[$action] . ' = :value WHERE id = :id';
  $binds = [
    ':value' => $input,
    ':id' => $gym_id,
  ];
  $gym[$column_map[$action]] = $input;
}
if($query !== false) {
  my_query($query, $binds);

  $msg = get_gym_details($gym, true);
  $msg .= CR . CR . getTranslation('gym_saved');
  $update['callback_query']['from']['id'] = $user_id;
  $keys = edit_gym_keys($update, $gym_id, $gym['show_gym'], $gym['ex_gym'], $gym['gym_note'], $gym['address']);
}
// Remove back button from previous message to avoid confusion
editMessageText($modifiers['old_message_id'], $msg, $keys, $user_id, ['disable_web_page_preview' => 'true']);
