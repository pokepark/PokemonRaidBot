<?php
/**
 * Full and partial raid poll message.
 * @param $msg_array
 * @param $append
 * @param $skip
 * @return array
 */
function raid_poll_message($msg_array, $append, $skip = false)
{
  global $config;
  // Array key full already created?
  if(!(array_key_exists('full', $msg_array))) {
    $msg_array['full'] = '';
  }

  //Raid picture?
  $msg_array['full'] .= $append;
  if($config->RAID_PICTURE && $skip == false) {
    // Array key short already created?
    if(!(array_key_exists('short', $msg_array))) {
      $msg_array['short'] = '';
    }

    $msg_array['short'] .= $append;
  }

  return $msg_array;
}
