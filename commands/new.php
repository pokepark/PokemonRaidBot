<?php
// Write to log.
debug_log('NEW()');

// For debug.
//debug_log($update);
//debug_log($data);

// Get lat and lon from message text. (remove: "/new ")
$coords = trim(substr($update['message']['text'], 4));

// #TODO
// Add check to validate latitude and longitude
// If lat and lon = valid
//     button to create raid
// else
//     no button and error message

// Create keys array.
$keys = [
	    [
	        [
	            'text'          => getTranslation('create_a_raid'),
	            'callback_data' => '0:raid_create:' . $coords
	        ]
	    ]
	];

$msg = getTranslation('coordination_succes');

// Send message.
send_message($update['message']['from']['id'], $msg, $keys, ['reply_markup' => ['selective' => true, 'one_time_keyboard' => true]]);

exit;
