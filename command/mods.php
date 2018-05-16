<?php
// Write to log.
debug_log('MODS()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access - user must be admin!
bot_access_check($update, BOT_ADMINS);

// Init empty keys array.
$keys = array();

// Create keys array.
$keys = [
    [
        [
            'text'          => getTranslation('list'),
            'callback_data' => '0:mods:list'
        ],
        [
            'text'          => getTranslation('add'),
            'callback_data' => '0:mods:add'
        ],
        [
            'text'          => getTranslation('delete'),
            'callback_data' => '0:mods:delete'
        ]
    ]
];

// Set message.
$msg = '<b>' . getTranslation('mods_list_add_delete') . ':</b>';

// Send message.
send_message($update['message']['chat']['id'], $msg, $keys, ['reply_markup' => ['selective' => true, 'one_time_keyboard' => true]]);

exit;
