<?php
// Write to log.
debug_log('START()');

// For debug.
//debug_log($update);
//debug_log($data);

// Create keys array.
$keys = [
	    [
	        [
	            'text'          => getTranslation('create_a_raid'),
		    'callback_data' => '0:raid_by_gym_letter:0',
	        ]
	    ]
	];

// Set message.
$msg = '<b>' . getTranslation('raid_by_gym') . '</b>' . CR2 . CR .  getTranslation('send_location') ;

// Send message.
send_message($update['message']['chat']['id'], $msg, $keys, ['reply_markup' => ['selective' => true, 'one_time_keyboard' => true]]);

exit;
