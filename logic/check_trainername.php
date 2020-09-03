<?php
/**
 * Delivers Trainername or Telegram-name
 * @param array $row
 * @return array $row
 */
function check_trainername($row){
    global $config;
    if($config->CUSTOM_TRAINERNAME){
        if($row['trainername'] == "" || is_null($row['trainername']) || empty($row['trainername'])){
            // leave name as it is (Trainername was not configured)
        }else{
            $row['name'] = $row['trainername'];
        }
    }
        
    return $row;
}

?>