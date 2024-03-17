<?php

// Write to log.
debug_log('post_raid()');

// For debug.
//debug_log($update);
//debug_log($data);

// Get raid id.
$id = $data['id'];

// Get chat id.
$chat = $data['arg'];

require_once(LOGIC_PATH . '/send_raid_poll.php');
$tg_json = send_raid_poll($id, [create_chat_object([$chat])]);

// Telegram multicurl request.
curl_json_multi_request($tg_json);
