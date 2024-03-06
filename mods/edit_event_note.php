<?php
require_once(LOGIC_PATH . '/show_raid_poll_small.php');

// Set the id.
$raid_id = $data['r'];

// Set the arg.
$mode = $data['m'] ?? '';

// Set the user id.
$userid = $update['callback_query']['from']['id'];

$msg = '';
$keys = [];

$callback_response = 'OK';

// Get the raid
$raid = get_raid($raid_id);

$msg = '';
$msg .= getTranslation('raid_saved') . CR;
$msg .= show_raid_poll_small($raid, false) . CR2;

if($mode == 'e') {
  $msg.= getTranslation('event_note_edit') . ': ';

  // Create an entry to user_input table
  $modifiers = json_encode(array('id' => $raid_id, 'old_message_id' => $update['callback_query']['message']['message_id'])); // Save the raid id and the message id to db so we can delete it later
  $handler = 'save_event_note';  // call for mods/save_event_note.php after user posts the answer

  my_query('INSERT INTO user_input SET user_id=:userId, modifiers=:modifiers, handler=:handler', ['userId' => $userid, 'modifiers' => $modifiers, 'handler' => $handler]);
}elseif($mode == 'c') {
  my_query('DELETE FROM user_input WHERE user_id = ?', [$userid]);
  require_once('edit_save.php');
  exit();
}else {
  if($raid['event'] == EVENT_ID_EX) {
    $event_name = getTranslation('Xstars');
  }else {
    $q = my_query('SELECT name FROM events WHERE id = ?', [$raid['event']]);
    $res = $q->fetch();
    $event_name = $res['name'];
  }

  $msg.= getTranslation('event') . ': <b>' . $event_name . '</b>' . CR;
  $msg.= getTranslation('event_add_note_description');

  // Create an entry to user_input table
  $modifiers = json_encode(array('id' => $raid_id, 'old_message_id' => $update['callback_query']['message']['message_id'])); // Save the raid id and the message id to db so we can delete it later
  $handler = 'save_event_note';  // call for mods/save_event_note.php after user posts the answer

  my_query('INSERT INTO user_input SET user_id = :userId, modifiers = :modifiers, handler = :handler', ['userId' => $userid, 'modifiers' => $modifiers, 'handler' => $handler]);
}
$keys[][] = button(getTranslation('cancel'), ['edit_event_note', 'r' => $raid_id, 'm' => 'c']);

// Answer callback.
answerCallbackQuery($update['callback_query']['id'], $callback_response);

// Edit message.
edit_message($update, $msg, $keys, false);
