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
        SET trainercode =   '{$trainercode}'
        WHERE user_id =     {$target_user_id}
        "
    );
    
    // Remove back button from previous message to avoid confusion
    edit_message_keyboard($modifiers['old_message_id'], [], $target_user_id);
    
    // Create the keys.
    $keys = [
        [
            [
                'text'          => getTranslation('back'),
                'callback_data' => '0:trainer:0'
            ],
            [
                'text'          => getTranslation('done'),
                'callback_data' => '0:exit:1'
            ]
        ]
    ];

    // confirm Trainercode-Change
    send_message($target_user_id, getTranslation('trainercode_success').' <b>'.$trainercode.'</b>', $keys);
}else{
    // Trainer Code got unallowed Chars -> Error-Message
    sendMessage($target_user_id, getTranslation('trainercode_fail'));
    exit();
}
