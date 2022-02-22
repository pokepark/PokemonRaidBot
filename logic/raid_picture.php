<?php

/**
 * Get full raidpicture.php URL
 * @param array $raid Raid array from get_raid()
 * @param bool $standalone Clear the bottom right corner of the photo from text
 * @return string
 */
function raid_picture_url($raid, $standalone = false)
{
  global $config;

  // If any params go missing from the url the image generated will likely be served from cache
  // So let's warn people if were generating bogus URLs
  foreach (array('pokemon', 'pokemon_form', 'start_time', 'end_time', 'gym_id') as $key) {
    if (!array_key_exists($key, $raid) || $raid[$key] == '' || $raid[$key] == '-') {
      error_log("raid_picture; Insufficient parameters for raidpicture: '{$key}:{$raid[$key]}'");
    }
  }

  $start_time = strtotime($raid['start_time']);
  $end_time = strtotime($raid['end_time']);
  if($raid['event'] == EVENT_ID_EX) $ex_raid = '1'; else $ex_raid = '0';
  $picture_url = "{$config->RAID_PICTURE_URL}?pokemon={$raid['pokemon']}&pokemon_form={$raid['pokemon_form']}&gym_id={$raid['gym_id']}&start_time={$start_time}&end_time={$end_time}&ex_raid={$ex_raid}";
  if($standalone) $picture_url .= '&sa=1';
  if($raid['costume'] != 0) $picture_url .= '&costume='.$raid['costume'];
  debug_log('raid_picture_url: ' . $picture_url);
  return $picture_url;
}
?>
