<?php
/**
 * Changes the trainercode
 * @param array $update
 */
function change_trainercode($update, $answer){
    // get UserID from Message
    $userid = $update['message']['from']['id'];

	if($answer['user_id'] == $userid) // Check if Answer is for the right User
	{
        // Trim entry to only numbers
        $trainercode = preg_replace('/\D/', '', $update['message']['text']);

        // Check that Code is 12 digits long
		if(strlen($trainercode)==12){
            // Store new Trainercode to DB
			my_query(
                "
                UPDATE users
                SET setcode_time =  NULL,
                    trainercode =   '{$trainercode}'
                WHERE user_id =     {$userid}
                "
            );
            // confirm Trainercode-Change
			sendMessage($userid, getTranslation('trainercode_success').' <b>'.$trainercode.'</b>');
		}else{
            // Trainer Code got unallowed Chars -> Error-Message
			sendMessage($userid, getTranslation('trainercode_fail'));
            // Set setcode_time to 'still waiting for Code-Change'
			my_query(
                "
                UPDATE users
                SET setcode_time =  DATE_ADD(NOW(), INTERVAL 1 HOUR)
                WHERE user_id =     {$userid}
                "
            );
		}
    }
}

?>