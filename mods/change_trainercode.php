<?php

// get UserID from Message
$target_user_id = $update['message']['from']['id'];

// Trim entry to only numbers
$trainercode = preg_replace('/\D/', '', $update['message']['text']);

// Check that Code is 12 digits long
if(strlen($trainercode)==12){
    // Store new Trainercode to DB
    my_query(
        "
        UPDATE users
        SET trainercode_time =  NULL,
            trainercode =   '{$trainercode}'
        WHERE user_id =     {$target_user_id}
        "
    );
    // confirm Trainercode-Change
    sendMessage($target_user_id, getTranslation('trainercode_success').' <b>'.$trainercode.'</b>');
}else{
    // Trainer Code got unallowed Chars -> Error-Message
    sendMessage($target_user_id, getTranslation('trainercode_fail'));
    // Set trainercode_time to 'still waiting for Code-Change'
    my_query(
        "
        UPDATE users
        SET trainercode_time =  DATE_ADD(NOW(), INTERVAL 1 HOUR)
        WHERE user_id =     {$target_user_id}
        "
    );
    exit();
}
