<?php

/**
 * Get full raidpicture.php URL
 * @param $raid
 * @return string
 */
function raid_picture_url($raid)
{
  global $config;

  // If any params go missing from the url the image generated will likely be served from cache
  // So let's warn people if were generating bogus URLs
  foreach (array('pokemon', 'pokemon_form', 'id') as $key) {
    if (!array_key_exists($key, $raid) || $raid[$key] == '' || $raid[$key] == '-') {
      error_log("raid_picture; Insufficient parameters for raidpicture: '{$key}:{$raid[$key]}'");
    }
  }

  $dedupe = strtotime($raid['end_time']); // added to the end to prevent Telegram caching raids that have a different end_time
  $picture_url = "{$config->RAID_PICTURE_URL}?pokemon={$raid['pokemon']}-{$raid['pokemon_form']}&raid={$raid['id']}&h={$dedupe}";
  debug_log('raid_picture_url: ' . $picture_url);
  return $picture_url;
}
?>
