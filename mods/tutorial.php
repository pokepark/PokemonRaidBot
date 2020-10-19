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
$action = $data['arg'];
$user_id = $update['callback_query']['from']['id'];
$new_user = new_user($user_id);
$tutorial_count = count($tutorial);

if($action == "end") {
    answerCallbackQuery($update['callback_query']['id'], "OK!");
    delete_message($update['callback_query']['message']['chat']['id'],$update['callback_query']['message']['message_id']);
    if($new_user) {
        my_query("UPDATE users SET tutorial = '1' WHERE user_id = '{$user_id}'");

        send_message($user_id, $tutorial_done, $keys);
    }
    
    
    $q = my_query("SELECT level, team FROM users WHERE user_id='{$user_id}' LIMIT 1");
    $row = $q->fetch();

    if(($row['level']==0 or $row['team']=="" or $row['team']==NULL)) {
        $msg = getTranslation("tutorial_no_user_info_set");
        $keys = [
        [
            [
                'text'          => getTranslation("yes"),
                'callback_data' => '0:trainer:0'
            ],
            [
                'text'          => getTranslation("no"),
                'callback_data' => '0:exit:1'
            ]
        ]
        ];
        send_message($user_id,$msg,$keys);
    }


}else {

    if($new_user && isset($tutorial[($action)]['msg_new'])) {
        $msg = $tutorial[($action)]['msg_new'];
    }else {
        $msg =  $tutorial[($action)]['msg'];
    }
    $photo =  $tutorial[$action]['photo'];
    $keys = [];
    if($action > 0) {
        $keys = [
        [
            [
                'text'          => getTranslation("back") . " (".($action)."/".($tutorial_count).")",
                'callback_data' => "0:tutorial:".($action-1)
            ]
        ]
        ];    
    }
    if($action < ($tutorial_count - 1)) {
        $keys[0][] = [
                'text'          => getTranslation("next") . " (".($action+2)."/".($tutorial_count).")",
                'callback_data' => "0:tutorial:".($action+1)
        ];
    }else {
        $keys[0][] = [
                'text'          => getTranslation("done"),
                'callback_data' => "0:tutorial:end"
        ];
    }
}
answerCallbackQuery($update['callback_query']['id'], "OK!");
editMessageMedia($update['callback_query']['message']['message_id'], $msg, $keys, $update['callback_query']['message']['chat']['id'], ['disable_web_page_preview' => 'true'],false,$photo);
?>