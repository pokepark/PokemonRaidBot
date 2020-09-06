<?php
/**
 * Delivers Trainername (if not set) ->  Telegram-@Nick (if not set) -> Telegram-name
 * @param array $row
 * @return array $row
 */
function check_trainername($row){
    global $config;
    if($config->CUSTOM_TRAINERNAME){ // if Custom Trainername is enabled by config
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

function check_for_empty_string($string){
  if($string == "" || is_null($string) || empty($string)){
    return true;
  }
  return false;
}

?>
