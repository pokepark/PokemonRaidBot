<?php
// Write to log.
debug_log('TUTORIAL()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
$botUser->accessCheck('tutorial');

// Tutorial
if(is_file(ROOT_PATH . '/config/tutorial.php')) {
  require_once(ROOT_PATH . '/config/tutorial.php');
}
$action = $data['p'];
$user_id = $update['callback_query']['from']['id'];
$new_user = new_user($user_id);
$tutorial_count = count($tutorial);

if($action == 'end') {
  answerCallbackQuery($update['callback_query']['id'], 'OK!');
  delete_message($update['callback_query']['message']['chat']['id'],$update['callback_query']['message']['message_id']);
  if($new_user) {
    my_query('UPDATE users SET tutorial = ? WHERE user_id = ?', [$data['l'], $user_id]);
    send_message($user_id, $tutorial_done, []);

    // Post the user id to external address if specified
    if(isset($config->TUTORIAL_COMPLETED_CURL_ADDRESS) && $config->TUTORIAL_COMPLETED_CURL_ADDRESS != '') {
      $post_array = [
        'tutorial' => 'OK',
        'user_id' => $user_id
      ];
      $json = json_encode($post_array);
      $URL = $config->TUTORIAL_COMPLETED_CURL_ADDRESS;
      $curl = curl_init($URL);

      curl_setopt($curl, CURLOPT_HEADER, false);
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
      curl_setopt($curl, CURLOPT_POST, true);
      curl_setopt($curl, CURLOPT_POSTFIELDS, $json);
      curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
      curl_setopt($curl, CURLOPT_TIMEOUT, 10);

      // Use Proxyserver for curl if configured
      if ($config->CURL_USEPROXY && !empty($config->CURL_PROXYSERVER)) {
        curl_setopt($curl, CURLOPT_PROXY, $config->CURL_PROXYSERVER);
      }

      // Execute curl request.
      $json_response = curl_exec($curl);

      // Close connection.
      curl_close($curl);
    }
  }
  $q = my_query('SELECT level, team FROM users WHERE user_id = ? LIMIT 1', [$user_id]);
  $row = $q->fetch();
  if($row['level'] == 0 or $row['team'] == '' or $row['team'] == NULL) {
    $msg = getTranslation('tutorial_no_user_info_set');
    $keys[0][0] = button(getTranslation('yes'), 'trainer');
    $keys[0][1] = button(getTranslation('no'), ['exit', 'd' => '1']);
    send_message($user_id,$msg,$keys);
  }
  exit();
}

if($new_user && isset($tutorial[($action)]['msg_new'])) {
  $msg = $tutorial[($action)]['msg_new'];
}else {
  $msg =  $tutorial[($action)]['msg'];
}
$photo =  $tutorial[$action]['photo'];
$keys = [];
if($action > 0) {
  $keys[0][] = button(getTranslation('back') . ' ('.($action).'/'.($tutorial_count).')', ['tutorial', 'p' => $action-1]);
}
if($action < ($tutorial_count - 1)) {
  $keys[0][] = button(getTranslation('next') . ' ('.($action+2).'/'.($tutorial_count).')', ['tutorial', 'p' => $action+1]);
}else {
  $keys[0][] = button(getTranslation('done'), ['tutorial', 'p' => 'end', 'l' => $tutorial_grant_level]);
}
answerCallbackQuery($update['callback_query']['id'], 'OK!');
editMessageMedia($update['callback_query']['message']['message_id'], $msg, $photo, false, $keys, $update['callback_query']['message']['chat']['id'], ['disable_web_page_preview' => 'true']);
