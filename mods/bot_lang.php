<?php
// Write to log.
debug_log('bot_lang()');

// For debug.
//debug_log($update);
//debug_log($data);

// Access check.
bot_access_check($update, 'trainer');


$keys = [];

if($data['arg'] != '0') {
    $query = "
        UPDATE  users 
        SET     lang_manual = 1,
                lang= :lang
        WHERE   user_id = :user_id
        ";
    $q = $dbh->prepare($query);
    $q->execute([
            'lang' => $data['arg'],
            'user_id' => $update['callback_query']['from']['id']
        ]);
    $new_lang_internal = $languages[$data['arg']];
    $msg = getTranslation('new_lang_saved', true, $new_lang_internal);
    $keys[] = [
        [
            'text'          => getTranslation('back', true, $new_lang_internal),
            'callback_data' => '0:trainer:0'
        ],
        [
            'text'          => getTranslation('done', true, $new_lang_internal),
            'callback_data' => '0:exit:1'
        ]
    ];
    $callback_msg = $msg;
} else {
    foreach($languages as $lang_tg => $lang_internal) {
        $keys[][] = [            
            'text'          => getTranslation('lang_name', true, $lang_internal),
            'callback_data' => '0:bot_lang:'.$lang_tg
        ];
    }
    $keys[] = [
        [
            'text'          => getTranslation('back'),
            'callback_data' => '0:trainer:0'
        ],
        [
            'text'          => getTranslation('done'),
            'callback_data' => '0:exit:1'
        ]
    ];
    $msg = getTranslation('change_lang').':';
    $callback_msg = getTranslation('change_lang');
}

// Answer callback.
answerCallbackQuery($update['callback_query']['id'], $callback_msg);

// Edit message.
edit_message($update, $msg, $keys, false);

// Exit.
$dbh = null;
exit();

?>