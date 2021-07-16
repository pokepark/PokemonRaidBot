<?php
/**
 * Get user.
 * @param $user_id
 * @return message
 */
function get_user($user_id)
{
    global $config;
    // Get user details.
    $rs = my_query(
        "
        SELECT *
        FROM   users
        WHERE  user_id = {$user_id}
        "
    );

    // Fetch the row.
    $row = $rs->fetch();
    // get Username
    $row = check_trainername($row);
    // Build message string.
    $msg = '';

    // Add name.
    $msg .= getTranslation('name') . ': <a href="tg://user?id=' . $row['user_id'] . '">' . htmlspecialchars($row['name']) . '</a>' . CR;

    if($config->RAID_POLL_SHOW_TRAINERCODE){ // is Trainercode enabled?
        // Unknown trainercode.
        if ($row['trainercode'] === NULL) {
            $msg .= getTranslation('trainercode') . ': ' . getTranslation('code_missing') . CR;
        // Known Trainercode.
        } else {
            $msg .= getTranslation('trainercode') . ': ' . $row['trainercode'] . CR;
        }
    }

    // Unknown team.
    if ($row['team'] === NULL) {
        $msg .= getTranslation('team') . ': ' . $GLOBALS['teams']['unknown'] . CR;
    // Known team.
    } else {
        $msg .= getTranslation('team') . ': ' . $GLOBALS['teams'][$row['team']] . CR;
    }

    // Add level.
    if ($row['level'] != 0) {
        $msg .= getTranslation('level') . ': <b>' . $row['level'] . '</b>' . CR;
    }

    return $msg;
}

/**
 * Delivers Trainername (if not set) ->  Telegram-@Nick (if not set) -> Telegram-name
 * @param array $row
 * @return array $row
 */
function check_trainername($row){
    global $config;
    if($config->CUSTOM_TRAINERNAME==true){ // if Custom Trainername is enabled by config
        if(check_for_empty_string($row['trainername'])){ // trainername not set by user
            // check if Telegram-@Nick is set
              if(check_for_empty_string($row['nick'])){
                // leave Telegram-name as it is (Trainername and Telegram-@Nick were not configured by user)
              }else{
                // set Telegram-@Nick as Name inside the bot
                $row['name'] = $row['nick'];
              }
        }else{
            // Trainername is configured by User
            $row['name'] = $row['trainername'];
        }
    }else{ // Custom Trainername is disabled by config
      // check if Telegram-@Nick is set
      if(check_for_empty_string($row['nick'])){
        // do nothing -> leave Telegram-name
      }else{
        // set Telegram-@Nick as Name inside the bot
        $row['name'] = $row['nick'];
      }
    }

    return $row;
}

/**
 * Checks if String is empty
 * @param String $string
 * @return boolean | true = empty | false = not empty
 */
function check_for_empty_string($string){
  if($string == "" || is_null($string) || empty($string)){
    return true;
  }
  return false;
}

/**
 * Changes the trainername
 * @param array $update
 * @param array $answer
 */
function change_trainername($update, $answer){
    // get UserID from Message
    $userid = $update['message']['from']['id'];

	if($answer['user_id'] == $userid) // Check if Answer is for the right User
	{
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
		}
    }
}

/**
 * Checks what Message the User entered
 * @param array $update
 */
function evaluate_priv_message($update){

     // get UserID from Message
     $target_user_id = $update['message']['from']['id'];
     // Check if User requested a UserName or Trainercode Update via /trainer -> Name
 	   $rs = my_query(
         "
         SELECT *
         FROM users
         WHERE user_id = {$target_user_id}
         AND (
             trainername_time > NOW()
             OR
             trainercode_time > NOW()
             )
         "
     );

     $answer = $rs->fetch();

     // Check if the setname or setcode time was set for updating
     if(is_null($answer['trainername_time']) && is_null($answer['trainercode_time'])){
         // do nothing -> not matching with setting up Trainername or Trainercode
     }elseif(!is_null($answer['trainername_time']) && !is_null($answer['trainercode_time'])){
         // both were requested -> LiFo (Last in First out)
         if($answer['trainername_time'] < $answer['trainercode_time']){
             // change Trainercode
             change_trainercode($update,$answer);
         }else{
             // change Trainername
             change_trainername($update,$answer);
         }
     }elseif(!is_null($answer['trainername_time'])){
         // change Trainername
         change_trainername($update,$answer);
     }else{
         // change Trainercode
         change_trainercode($update,$answer);
     }

}

/**
 * Changes the trainercode
 * @param array $update
 * @param array $answer
 */
function change_trainercode($update, $answer){
    // get UserID from Message
    $target_user_id = $update['message']['from']['id'];

	  if($answer['user_id'] == $target_user_id)
    // Check if Answer is for the right User
	  {
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
		    }
    }
}
?>
