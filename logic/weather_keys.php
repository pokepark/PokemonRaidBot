<?php
/**
 * Weather keys.
 * @param $pokedex_id
 * @param $action
 * @param $arg
 * @return array
 */
function weather_keys($pokedex_id, $action, $arg)
{
  // Get the type, level and cp
  $data = explode("-", $arg);
  $weather_add = $data[0] . '-';
  $weather_value = $data[1];

  // Save and reset values
  $save_arg = 'save-' . $weather_value;
  $reset_arg = $weather_add . '0';

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

    // Add buttons for each weather.
    for ($i = 1; $i <= $last; $i = $i + 1) {
      // Get length of arg and split arg
      $weather_value_length = strlen((string)$weather_value);
      $weather_value_string = str_split((string)$weather_value);

      // Continue if weather got already selected
      if($weather_value_length == 1 && $weather_value == $i) continue;
      if($weather_value_length == 2 && $weather_value_string[0] == $i) continue;
      if($weather_value_length == 2 && $weather_value_string[1] == $i) continue;

      // Set new weather.
      $new_weather = $weather_add . ($weather_value == 0 ? '' : $weather_value) . $i;

      // Set keys.
      $keys[] = array(
        'text'          => $GLOBALS['weather'][$i],
        'callback_data' => $pokedex_id . ':' . $action . ':' . $new_weather
      );
    }
  }

  // Get the inline key array.
  $keys = inline_key_array($keys, 3);

  // Save and Reset key
  $keys[] = array(
    array(
      'text'          => EMOJI_DISK,
      'callback_data' => $pokedex_id . ':' . $action . ':' . $save_arg
    ),
    array(
      'text'          => getTranslation('reset'),
      'callback_data' => $pokedex_id . ':' . $action . ':' . $reset_arg
    )
  );

  return $keys;
}
