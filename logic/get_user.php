<?php
/**
 * Get user.
 * @param $user_id
 * @param $public
 * @param $return_row
 * @return message
 */
function get_user($user_id, $public = true, $return_row = false)
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
    // Build message string.
    $msg = '';

    $display_name = ['',''];
    if(!$public) $display_name[intval($row['display_name'])] = '-> ';

    // Add name.
    $msg .= $display_name[0] . getTranslation('name') . ': <a href="tg://user?id=' . $row['user_id'] . '">' . htmlspecialchars($row['name']) . '</a>' . CR;
    // Add name.
    $msg .= $display_name[1] . getTranslation('trainername') . ': ' . (check_for_empty_string($row['trainername']) ? getTranslation('not_set') : $row['trainername'] ) . CR;

    if($config->RAID_POLL_SHOW_TRAINERCODE){ // is Trainercode enabled?
        // Unknown trainercode.
        if ($row['trainercode'] === NULL) {
            $msg .= getTranslation('trainercode') . ': ' . getTranslation('not_set') . CR;
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
        $msg .= getTranslation('level') . ': <b>' . $row['level'] . '</b>' . CR . CR;
    }
    if(!$public) $msg .= getTranslation('display_name_explanation') . CR;

    if($return_row) {
        return [
                'message' => $msg, 
                'row' => $row
               ];
    }else {
        return $msg;
    }
}

/**
 * Delivers Trainername (if not set) ->  Telegram-@Nick (if not set) -> Telegram-name
 * @param array $row
 * @return array $row
 */
function check_trainername($row){
    global $config;
    // if Custom Trainername is enabled by config
    if($config->CUSTOM_TRAINERNAME == false || check_for_empty_string($row['trainername']) || (isset($row['display_name']) && $row['display_name'] != 1)){ // trainername not set by user
        // check if Telegram-@Nick is set
        if(!check_for_empty_string($row['nick']) && $config->RAID_POLL_SHOW_NICK_OVER_NAME){
            // set Telegram-@Nick as Name inside the bot
            $row['name'] = $row['nick'];
        }else{
            // leave Telegram-name as it is (Trainername and Telegram-@Nick were not configured by user)
        }
    }else{
        // Trainername is configured by User
        $row['name'] = $row['trainername'];
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
