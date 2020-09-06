<?php
/**
 * Checks what Message the User entered
 * @param array $update
 */
function evaluate_priv_message($update){

    // get UserID from Message
    $userid = $update['message']['from']['id'];
    // Check if User requested a UserName or Trainercode Update via /trainer -> Name
	$rs = my_query(
        "
        SELECT *
        FROM users
        WHERE user_id = {$userid}
        AND (
            setname_time > NOW()
            OR 
            setcode_time > NOW()
            )
        "
    );

    $answer = $rs->fetch();
    
    // Check if the setname or setcode time was set for updating
    if(is_null($answer['setname_time']) && is_null($answer['setcode_time'])){
        // do nothing -> not matching with setting up Trainername or Trainercode
    }elseif(!is_null($answer['setname_time']) && !is_null($answer['setcode_time'])){
        // both were requested -> LiFo (Last in First out)
        if($answer['setname_time'] < $answer['setcode_time']){
            // change Trainercode
            change_trainercode($update,$answer);
        }else{
            // change Trainername
            change_trainername($update,$answer);
        }
    }elseif(!is_null($answer['setname_time'])){
        // change Trainername
        change_trainername($update,$answer);
    }else{
        // change Trainercode
        change_trainercode($update,$answer);
    }

}


?>