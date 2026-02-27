<?php
/**
 * Weather keys.
 * @param $data
 * @return array
 */
function weather_keys($data)
{
  // Get the type, level and cp
  $weather_value = $data['w'] ?? '';

  // Init empty keys array.
  $keys = [];

  // Max amount of weathers a pokemon raid boss can have is 3 which means 999
  // Keys will be shown up to 99 and when user is adding one more weather we exceed 99, so we remove the keys then
  // This means we do not exceed the max amout of 3 weathers a pokemon can have :)
  // And no, 99 is not a typo if you read my comment above :P
  if($weather_value <= 99) {
    // Get last number from weather array
    end($GLOBALS['weather']);
    $last = key($GLOBALS['weather']);
    $buttonData = $data;
    // Add buttons for each weather.
    for ($i = 1; $i <= $last; $i = $i + 1) {
      // Continue if weather got already selected
      if (preg_match('/'.$i.'/', $weather_value))
        continue;

      // Set new weather.
      $buttonData['w'] = $weather_value . $i;

      // Set keys.
      $keys[] = button($GLOBALS['weather'][$i], $buttonData);
    }
  }
  // Get the inline key array.
  $keys = inline_key_array($keys, 3);

  $saveData  = $resetData = $data;
  $saveData['a'] = 'save';
  unset($resetData['w']);
  // Save and Reset key
  $keys[] = array(
    button(EMOJI_DISK, $saveData),
    button(getTranslation('reset'), $resetData)
  );

  return $keys;
}
