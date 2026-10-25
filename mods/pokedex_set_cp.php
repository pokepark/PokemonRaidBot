<?php
// Write to log.
debug_log('pokedex_set_cp()');
require_once(LOGIC_PATH . '/get_pokemon_info.php');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
$botUser->accessCheck('pokedex');

// Set the id.
$pokedex_id = $data['p'];

// Split pokedex_id and form
$dex_id_form = explode('-',$pokedex_id,2);
$dex_id = $dex_id_form[0];
$dex_form = $dex_id_form[1];

// Get the type, level and cp
$cp_type = $data['t'];
$cp_level = $data['l'];
$cp_value = $data['cp'] ?? 0;

// Set boosted string
$boosted = ($cp_level == 25) ? '_weather_cp' : '_cp';

// Action to do: Save or add digits to cp
$action = $data['a'];

// Get current CP values
$pokemon = get_pokemon_info($dex_id, $dex_form);
$current_cp = $pokemon[$cp_type . $boosted];

// Log
debug_log('New CP Type: ' . $cp_type);
debug_log('New CP Level: ' . $cp_level);
debug_log('New CP: ' . $cp_value);
debug_log('Old CP: ' . $current_cp);
debug_log('Action: ' . $action);

// Add digits to cp
if($action == 'add') {
  // Init empty keys array.
  $keys = [];

  // Get the keys.
  $keys = cp_keys($data);

  // Back and abort.
  $keys[] = [
    button(getTranslation('back'), ['pokedex_edit_pokemon', 'p' => $pokedex_id]),
    button(getTranslation('abort'), 'exit')
  ];

  // Build callback message string.
  $callback_response = 'OK';

  // Set the message.
  $msg = getTranslation('raid_boss') . ': <b>' . get_local_pokemon_name($dex_id, $dex_form) . ' (#' . $dex_id . ')</b>' . CR;
  $msg .= getTranslation('pokedex_current_cp') . ' ' . $current_cp . CR . CR;
  $msg .= '<b>' .getTranslation('pokedex_' . $cp_type . $boosted) . ': ' . $cp_value . '</b>';

// Save cp to database
} else if($action == 'save') {
  // Set column name.
  $cp_column = $cp_type . $boosted;

  // Update cp of pokemon.
  $rs = my_query('
    UPDATE  pokemon
    SET     ' . $cp_column . ' = ?
    WHERE   pokedex_id = ?
    AND     pokemon_form_id = ?
    ', [$cp_value, $dex_id, $dex_form]
  );

  // Back to pokemon and done keys.
  $keys[0][0] = button(getTranslation('back') . ' (' . get_local_pokemon_name($dex_id, $dex_form) . ')', ['pokedex_edit_pokemon', 'p' => $pokedex_id]);
  $keys[0][1] = button(getTranslation('done') . ' (' . get_local_pokemon_name($dex_id, $dex_form) . ')', ['exit', 'd' => '1']);

  // Build callback message string.
  $callback_response = getTranslation('pokemon_saved') . ' ' . get_local_pokemon_name($dex_id, $dex_form);

  // Set the message.
  $msg = getTranslation('pokemon_saved') . CR;
  $msg .= '<b>' . get_local_pokemon_name($dex_id, $dex_form) . ' (#' . $dex_id . ')</b>' . CR . CR;
  $msg .= getTranslation('pokedex_' . $cp_type . $boosted) . ': <b>' . $cp_value . '</b>';
}

// Telegram JSON array.
$tg_json = array();

// Answer callback.
$tg_json[] = answerCallbackQuery($update['callback_query']['id'], $callback_response, true);

// Edit message.
$tg_json[] = edit_message($update, $msg, $keys, false, true);

// Telegram multicurl request.
curl_json_multi_request($tg_json);

function cp_keys($data)
{
  // Get the type, level and cp
  $old_cp = $data['cp'] ?? '';

  // Save and reset values
  $saveData = $resetData = $data;
  $saveData['a'] = 'save';
  unset($resetData['cp']);

  // Init empty keys array.
  $keys = [];

  // Max CP is 9999 and no the value 999 is not a typo!
  // Keys will be shown up to 999 and when user is adding one more number we exceed 999, so we remove the keys then
  // This means we do not exceed a Max CP of 9999 :)
  if($old_cp <= 999) {
    $buttonData = $data;
    // Add keys 0 to 9
    /**
     * 7 8 9
     * 4 5 6
     * 1 2 3
     * 0
    */

    // 7 8 9
    foreach ([7, 8, 9, 4, 5, 6, 1, 2, 3] as $i) {
      // Set new cp
      $buttonData['cp'] = $old_cp . $i;
      // Set keys.
      $keys[] = button($i, $buttonData);
    }

    // 0
    $buttonData['cp'] = $old_cp . '0';
    $keys[] = button('0', $buttonData);
  }

  // Save
  $keys[] = button(EMOJI_DISK, $saveData);

  // Reset
  $keys[] = button(getTranslation('reset'), $resetData);

  // Get the inline key array.
  $keys = inline_key_array($keys, 3);

  return $keys;
}
