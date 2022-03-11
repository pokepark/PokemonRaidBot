<?php
// Write to log.
debug_log('TUTORIAL()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
bot_access_check($update, 'tutorial');

// Tutorial
if(is_file(ROOT_PATH . '/config/tutorial.php')) {
    require_once(ROOT_PATH . '/config/tutorial.php');
}
$new_user = new_user($update['message']['from']['id']);
if($new_user) {
	$msg = $tutorial[0]['msg_new'];
}else {
	$msg = $tutorial[0]['msg'];
}
$keys = [
[
	[
		'text'          => getTranslation("next"),
		'callback_data' => '0:tutorial:1'
	]
]
];
$photo = $tutorial[0]['photo'];
send_photo($update['message']['from']['id'], $photo, $msg, $keys, ['disable_web_page_preview' => 'true'],false);
?>