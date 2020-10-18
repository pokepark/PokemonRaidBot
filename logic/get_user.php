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
?>
