<?php
/**
 * Check attendance time against anytime.
 * @param $time
 */
function check_time($time)
{
    // Raid anytime?
    if(strcmp($time,'0000-00-00 00:00:00')===0){
      return getTranslation('anytime');
    } else {
      return dt2time($time);
    }
}

?>
