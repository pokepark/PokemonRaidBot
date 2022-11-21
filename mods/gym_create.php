<?php
// Write to log.
debug_log('gym_create()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
$botUser->accessCheck($update, 'gym-edit');

function insertUserInput($userId, $stage, $oldMessageId, $gymId = 0) {
  global $dbh;
  // Create an entry to user_input table
  $modifierArray = ['stage' => $stage + 1, 'oldMessageId' => $oldMessageId];
  if($gymId !== 0) $modifierArray['gymId'] = $gymId;
  $modifiers = json_encode($modifierArray);
  $handler = 'gym_create';

  my_query('INSERT INTO user_input SET user_id = :userId, modifiers = :modifiers, handler = :handler', [':userId' => $userId, ':modifiers' => $modifiers, ':handler' => $handler]);
  return $dbh->lastInsertId();
}
function respondToUser($userId, $oldMessageId = 0, $editMsg = '', $editKeys = [], $sendMsg = '', $sendKeys = [], $callbackMsg = '', $callbackId = 0) {
  if($callbackId != 0) answerCallbackQuery($callbackId, $callbackMsg);
  if($editMsg != '') editMessageText($oldMessageId, $editMsg, $editKeys, $userId, ['disable_web_page_preview' => 'true']);
  if($sendMsg != '') send_message($userId, $sendMsg, $sendKeys, ['disable_web_page_preview' => 'true']);
}
if(isset($data['arg'])) {
  // Split the arg.
  $split_arg = explode('-', $data['arg']);
  $action = $split_arg[0] ?? false;
  $deleteId = $split_arg[1] ?? false;
}
// Set keys.
$keys = [];

$stage = $modifiers['stage'] ?? 1;

if(isset($action) && $action == 'abort') {
  my_query("DELETE FROM user_input WHERE id = :deleteId", ['deleteId' => $deleteId]);
  $msg = getTranslation("action_aborted");
  editMessageText($update['callback_query']['message']['message_id'], $msg, [], $update['callback_query']['from']['id']);
}else {
  if($stage == 1) {
    $callbackResponse = getTranslation('here_we_go');
    $callbackId = $update['callback_query']['id'];

    $userId = $update['callback_query']['from']['id'];
    $oldMessageId = $update['callback_query']['message']['message_id'];

    $userInputId = insertUserInput($userId, $stage, $oldMessageId);

    $editMsg = getTranslation("gym_create") . ':';
    $editKeys[0][] = [
        'text' => getTranslation("abort"),
        'callback_data' => '0:gym_create:abort-' . $userInputId
      ];
    $sendMsg = EMOJI_HERE . getTranslation('gym_gps_instructions') . CR;
    $sendMsg .= getTranslation('gym_gps_example');
    respondToUser($userId, $oldMessageId, $editMsg, $editKeys, $sendMsg, [], $callbackResponse, $callbackId);
  }else {
    $userId = $update['message']['from']['id'];
    $oldMessageId = $modifiers['oldMessageId'];

    if($stage == 2) {
      $input = $update['message']['text'];
      $reg_exp_coordinates = '^[-+]?([1-8]?\d(\.\d+)?|90(\.0+)?),\s*[-+]?(180(\.0+)?|((1[0-7]\d)|([1-9]?\d))(\.\d+)?)$^';
      if(preg_match($reg_exp_coordinates, $input)) {
        [$lat,$lon] = explode(',', $input, 2);
        my_query('INSERT INTO gyms (gym_name, lat, lon) VALUES (\'unknown\', :lat, :lon)', [':lat' => $lat, ':lon' => $lon]);
        $gymId = $dbh->lastInsertId();

        $userInputId = insertUserInput($userId, $stage, $oldMessageId, $gymId);
        $msg = EMOJI_PENCIL . getTranslation('gym_name_instructions');
        respondToUser($userId, 0, '', [], $msg);
      }else {
        $msg = getTranslation('gym_gps_coordinates_format_error');
        respondToUser($userId, 0, '', [], $msg);
        exit();
      }

    }elseif($stage == 3) {
      $input = trim($update['message']['text']);
      if(strlen($input) <= 255) {
        $gymId = $modifiers['gymId'];
        my_query('UPDATE gyms SET gym_name = :gym_name WHERE id = :gymId', [':gym_name' => $input, ':gymId' => $gymId]);

        $msg = getTranslation('gym_added');
        $keys[] = [
          [
            'text' => getTranslation('show_gym_details'),
            'callback_data' => 'N:gym_details:' . $gymId
          ]
        ];
        respondToUser($userId, $oldMessageId, 'OK', [], $msg, $keys);
      }else {
        $msg = getTranslation('gym_edit_text_too_long');
        respondToUser($userId, 0, '', [], $msg);
        exit();
      }
    }
  }
}
