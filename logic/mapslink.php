<?php
/**
 * Return Google Maps Link with Address of gym
 * @param $gym
 * @param $gym_address
 * @return string
 */
function mapslink($gym, $gym_address = '0'){

  global $config;
  $maps_route = true;
  $maps_link = '';
  //Getting Info from Config, if Route should be calculated on click the address in raid poll message
  if(isset($config->RAID_POLL_CALCULATE_MAPS_ROUTE)){
    $maps_route = $config->RAID_POLL_CALCULATE_MAPS_ROUTE;
  }
  //cut off the 0 in the lat and lon by rounding the float on the 6th digit after the decimal point
  $gym['lat'] = round($gym['lat'],6);
  $gym['lon'] = round($gym['lon'],6);
  //setting up alternative gym_address
  switch ($gym_address) {
    case '1':
      //using gym address as maps link
      $gym['address'] = 'https://www.google.com/maps?daddr=' . $gym['lat'] . ',' . $gym['lon'];
      break;
    case '0':
      // do nothing -> getting default address from gym/raid
      break;
    default: //using address from variable
      $gym['address'] = $gym_address;
      break;
  }

  if($maps_route){
    // getting link for route calculation
    $maps_link = '<a href="https://www.google.com/maps?daddr=' . $gym['lat'] . ',' . $gym['lon'] . '">' . $gym['address'] . '</a>';
  }else{
    // getting link for normal maps point
    $maps_link = '<a href="https://www.google.com/maps?ll=' . $gym['lat'] . ',' . $gym['lon'] . '&q=' . $gym['lat'] . ',' . $gym['lon'] . ' ">' . $gym['address'] . '</a>';
  }

  // returning Maps Link
  return $maps_link;
}
?>
