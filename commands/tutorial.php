<?php
// Write to log.
debug_log('TUTORIAL()');

// For debug.
//debug_log($update);
//debug_log($data);

$skipAccessCheck = $skipAccessCheck ?? 0;

// Check access.
if($skipAccessCheck !== 1) {
  $botUser->accessCheck('tutorial');
}
$userId = $update['chat_join_request']['user_chat_id'] ?? $update['message']['from']['id'];

// Tutorial
if(is_file(ROOT_PATH . '/config/tutorial.php')) {
  require_once(ROOT_PATH . '/config/tutorial.php');
}
// New user can already be set if this file was included from start.php. If not, set it here
$new_user = $new_user ?? new_user($userId);
$msg = ($new_user) ? $tutorial[0]['msg_new'] : $tutorial[0]['msg'];
$sendData = ['tutorial', 'p' => 1, 's' => $skipAccessCheck];
if(isset($update['chat_join_request']['chat']['id'])) {
  $sendData['c'] = $update['chat_join_request']['chat']['id'];
}
$keys[][] = button(getTranslation('next'), $sendData);
$photo = $tutorial[0]['photo'];
send_photo($userId, $photo, false, $msg, $keys, ['disable_web_page_preview' => 'true'],false);
