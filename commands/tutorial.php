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
// New user can already be set if this file was included from start.php. If not, set it here
$new_user = $new_user ?? new_user($update['message']['from']['id']);
$msg = ($new_user) ? $tutorial[0]['msg_new'] : $tutorial[0]['msg'];
$keys = [
  [
    [
      'text'          => getTranslation("next"),
      'callback_data' => '0:tutorial:1'
    ]
  ]
];
$photo = $tutorial[0]['photo'];
send_photo($update['message']['from']['id'], $photo, false, $msg, $keys, ['disable_web_page_preview' => 'true'],false);
