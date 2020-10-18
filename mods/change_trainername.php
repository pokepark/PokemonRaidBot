<?php

// get UserID from Message
$userid = $update['message']['from']['id'];

$returnValue = preg_match('/^[A-Za-z0-9]{0,15}$/', $update['message']['text']);
// Only numbers and alphabetic character allowed
if($returnValue){
    $trainername = $update['message']['text'];
    // Store new Gamer-Name to DB
    my_query(
        "
        UPDATE users
        SET trainername_time =  NULL,
            trainername =   '{$trainername}'
        WHERE user_id =     {$userid}
        "
    );
    // confirm Name-Change
    sendMessage($userid, getTranslation('trainername_success').' <b>'.$trainername.'</b>');
}else{
    // Trainer Name got unallowed Chars -> Error-Message
    sendMessage($userid, getTranslation('trainername_fail'));
    // Set trainername_time to 'still waiting for Name-Change'
    my_query(
        "
        UPDATE users
        SET trainername_time =  DATE_ADD(NOW(), INTERVAL 1 HOUR)
        WHERE user_id =     {$userid}
        "
    );
    exit();
}

