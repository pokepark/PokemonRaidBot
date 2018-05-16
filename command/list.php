<?php
// Write to log.
debug_log('LIST()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
bot_access_check($update, BOT_ACCESS);

// Init empty keys array.
$keys = array();

// Create keys array.
$keys = [
    [
        [
            'text'          => getTranslation('list'),
            'callback_data' => '0:raids_list:0'
        ]
    ],
    [
        [
            'text'          => getTranslation('overview_share'),
            'callback_data' => '0:overview_share:0'
        ],
        [
            'text'          => getTranslation('overview_delete'),
            'callback_data' => '0:overview_delete:0'
        ]
    ]
];

// Set message.
$msg = '<b>' . getTranslation('raids_list_share_overview') . ':</b>';

// Send message.
send_message($update['message']['chat']['id'], $msg, $keys, ['reply_markup' => ['selective' => true, 'one_time_keyboard' => true]]);

exit;
