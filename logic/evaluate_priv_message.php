<?php
/**
 * Checks what Message the User entered
 * @param array $update
 */
function evaluate_priv_message($update){

    // get UserID from Message
    $userid = $update['message']['from']['id'];
    // Check if User requested a UserName Update via /trainer -> Name
	$rs = my_query(
        "
        SELECT *
        FROM users
        WHERE user_id = {$userid}
        AND setname_time > NOW()
        "
    );

    $answer = $rs->fetch();
    
    // Check if the setname or setcode time was set for updating
    if(is_null($answer['setname_time'])){
        // do nothing -> not matching with setting up Trainername
    }else{
        // change Trainername
        change_trainername($update,$answer);
    }

}


?>