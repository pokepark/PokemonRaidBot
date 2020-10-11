<?php
/**
 * Check attendance time against anytime.
 * @param $time
 */
function check_time($time)
{
    // Raid anytime?
    if(strcmp($time, ANYTIME)===0){
      return getTranslation('anytime');
    } else {
      return dt2time($time);
    }
}

?>
