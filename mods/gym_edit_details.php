<?php
// Write to log.
debug_log('gym_edit_details()');
require_once(LOGIC_PATH . '/edit_gym_keys.php');
require_once(LOGIC_PATH . '/get_gym.php');
require_once(LOGIC_PATH . '/get_gym_details.php');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
$botUser->accessCheck('gym-edit');

// Get the id.
$gym_id = $data['g'];

// Split the arg.
$action = $data['a'] ?? '';
$value = $data['v'] ?? false;
$delete_id = $data['d'] ?? false;

// Set keys.
$keys = [];

debug_log('Changing the details for the gym with ID ' . $gym_id);

$gym = get_gym($gym_id);

// Did we receive a call to edit some gym data that requires a text input
if(in_array($action, ['name','note','gps','addr'])) {
  if($value == 'd') {
    my_query('DELETE FROM user_input WHERE id = :id', ['id' => $delete_id]);
    if($action == 'note') {
      my_query('UPDATE gyms SET gym_note = NULL WHERE id = :id', ['id' => $gym_id]);
      $gym['gym_note'] = '';
    }
    $msg = get_gym_details($gym, true);
    $keys = edit_gym_keys($update, $gym_id, $gym['show_gym'], $gym['ex_gym'], $gym['gym_note'], $gym['address']);
  }elseif($value == 'e') {
    my_query('DELETE FROM user_input WHERE id = ?', [$delete_id]);
    if($action == 'addr') {
      $addr = format_address(get_address($gym['lat'], $gym['lon']));
      my_query('UPDATE gyms SET address = :addr WHERE id = :id', ['addr' => $addr, 'id' => $gym_id]);
      $gym['address'] = $addr;
    }
    $msg = get_gym_details($gym, true);
    $keys = edit_gym_keys($update, $gym_id, $gym['show_gym'], $gym['ex_gym'], $gym['gym_note'], $gym['address']);
  }else {
    // Create an entry to user_input table
    $userid = $update['callback_query']['from']['id'];
    $modifiers = json_encode(array('id' => $gym_id, 'value' => $action, 'old_message_id' => $update['callback_query']['message']['message_id']));
    $handler = 'save_gym_info';

    my_query('INSERT INTO user_input SET user_id = :userid, modifiers = :modifiers, handler = :handler', [':userid' => $userid, ':modifiers' => $modifiers, ':handler' => $handler]);

    $msg = get_gym_details($gym, true);
    if($action == 'addr') $instructions = 'gym_address_instructions'; else $instructions = 'gym_'.$action.'_instructions';
    $msg .= CR . CR . '<b>' . getTranslation($instructions) . '</b>';
    if($action == 'gps') $msg .= CR. getTranslation('gym_gps_example');

    $keys[0][] = button(getTranslation('abort'), ['gym_edit_details', 'g' => $gym_id, 'a' => 'abort', 'd' => $dbh->lastInsertId()]);
    if($action == 'note' && !empty($gym['gym_note'])) {
      $keys[0][] = button(getTranslation('delete'), ['gym_edit_details', 'g' => $gym_id, 'a' => 'note', 'd' => $dbh->lastInsertId()]);
    }
    if($action == 'addr') {
      $keys[0][] = button(getTranslation('gym_save_lookup_result'), ['gym_edit_details', 'g' => $gym_id, 'a' => 'addr', 'd' => $dbh->lastInsertId()]);
    }
  }
}else {
  if($action == 'show') {
    $table = 'show_gym';
  }else if($action == 'ex') {
    $table = 'ex_gym';
  }else if($action == 'abort') {
    my_query('DELETE FROM user_input WHERE id = :delete_id', ['delete_id' => $delete_id]);
  }
  if(isset($table)) {
    my_query('
      UPDATE  gyms
      SET     ' . $table . ' = :value
      WHERE   id = :gym_id
      ', ['value' => $value, 'gym_id' => $gym_id]
    );
    $gym[$table] = $value;
  }
  $msg = get_gym_details($gym, true);
  $keys = edit_gym_keys($update, $gym_id, $gym['show_gym'], $gym['ex_gym'], $gym['gym_note'], $gym['address']);
}

// Build callback message string.
$callback_response = 'OK';

// Telegram JSON array.
$tg_json = array();

// Answer callback.
$tg_json[] = answerCallbackQuery($update['callback_query']['id'], $callback_response, true);

// Edit the message.
$tg_json[] = edit_message($update, $msg, $keys, ['disable_web_page_preview' => 'true'], true);

// Telegram multicurl request.
curl_json_multi_request($tg_json);
