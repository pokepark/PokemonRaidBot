<?php
require_once(LOGIC_PATH . '/mapslink.php');
/**
 * Get gym details.
 * @param $gym
 * @param $extended
 * @return string
 */
function get_gym_details($gym, $extended = false)
{
  global $config;
  // Add gym name to message.
  $msg = '<b>' . getTranslation('gym_details') . ':</b>' . CR . CR;
  $msg .= getTranslation('gym') . ':' . SP;
  $ex_raid_gym_marker = (strtolower($config->RAID_EX_GYM_MARKER) == 'icon') ? EMOJI_STAR : '<b>' . $config->RAID_EX_GYM_MARKER . '</b>';
  $msg .= ($gym['ex_gym'] ? $ex_raid_gym_marker . SP : '') . '<b>' . $gym['gym_name'] . '</b>';
  $msg .= CR;
  if($extended) $msg .= getTranslation('gym_stored_address') . CR;
  // Add maps link to message.
  $address = '';
  $lookupAddress = format_address(get_address($gym['lat'], $gym['lon']));
  if(!empty($gym['address'])) {
    $address = $gym['address'];
  } elseif(!$extended) {
    $address = $lookupAddress;
  }

  //Only store address if not empty
  if(!empty($address)) {
    //Use new address
    $msg .= mapslink($gym, $address) . CR;
  } elseif(!$extended) {
    //If no address is found show maps link
    $msg .= mapslink($gym, '1') . CR;
  }else {
    $msg .= getTranslation('none') . CR;
  }
  if($extended) {
    $msg .= getTranslation('gym_address_lookup_result') . ': ' . CR;
    $msg .= mapslink($gym, $lookupAddress) . CR;
  }

  // Add or hide gym note.
  if(!empty($gym['gym_note'])) {
    $msg .= EMOJI_INFO . SP . $gym['gym_note'] . CR;
  }

  // Get extended gym details?
  if(!$extended)
    return $msg;

  $msg .= CR . '<b>' . getTranslation('extended_gym_details') . '</b>';
  // Hidden gym?
  $translation = 'hidden_gym';
  if($gym['show_gym'] == 1) {
    // Normal gym?
    $translation = ($gym['ex_gym'] == 1) ? 'ex_gym' : 'normal_gym';
  }
  $msg .= CR . '-' . SP . getTranslation($translation);
  $msg .= CR . '-' . SP . getTranslation('gym_coordinates') . ': <code>' . (float)$gym['lat'] . ',' . (float)$gym['lon'].'</code>';

  return $msg;
}
