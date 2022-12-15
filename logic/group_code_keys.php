<?php
/**
 * Group code keys.
 * @param $raid_id
 * @param $action
 * @param $arg
 * @return array
 */
function group_code_keys($raid_id, $action, $arg)
{
  global $config;

  // Get current group code
  $data = explode('-', $arg);
  $poke1 = $data[0];
  $poke2 = $data[1];
  $poke3 = $data[2];

  // Send and reset values
  $reset_arg = '0-0-0-add';
  $send_arg = $poke1 . '-' . $poke2 . '-' . $poke3 . '-send';

  // Init empty keys array.
  $keys = [];

  // Show group code buttons?
  if($poke3 == 0) {

    // Add keys 1 to 9, where 1 = first pokemon, 9 = last pokemon
    /**
     * 1 2 3
     * 4 5 6
     * 7 8 9
    */

    $rc_poke = (explode(',',$config->RAID_CODE_POKEMON));
    foreach($rc_poke as $i) {
      // New code
      $new_code = ($poke1 == 0) ? ($i . '-0-0-add') : (($poke2 == 0) ? ($poke1 . '-' . $i . '-0-add') : (($poke3 == 0) ? ($poke1 . '-' . $poke2 . '-' . $i . '-add') : ($poke1 . '-' . $poke2 . '-' . $poke3 . '-send')));
      // Set keys.
      $keys[] = button(get_local_pokemon_name($i, '0'), [$action, 'r' => $raid_id, 'a' => $new_code]);
    }
  } else {
    // Send
    $keys[] = button(EMOJI_INVITE, [$action, 'r' => $raid_id, 'a' => $send_arg]);
  }

  // Reset
  $keys[] = button(getTranslation('reset'), [$action, 'r' => $raid_id, 'a' => $reset_arg]);

  // Get the inline key array.
  $keys = inline_key_array($keys, 3);

  return $keys;
}
