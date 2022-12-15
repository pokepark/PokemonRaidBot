<?php
// Write to log.
debug_log('pokedex_set_weather()');
require_once(LOGIC_PATH . '/get_pokemon_info.php');
require_once(LOGIC_PATH . '/get_weather_icons.php');
require_once(LOGIC_PATH . '/weather_keys.php');

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

// Get the action, old and new weather
$action = $data['a'] ?? 'add';
$new_weather = $data['w'] ?? 0;

$pokemon = get_pokemon_info($dex_id, $dex_form);
$old_weather = $pokemon['weather'];

// Log
debug_log('Action: ' . $action);
debug_log('Old weather: ' . $old_weather);
debug_log('New weather: ' . $new_weather);
// Init empty keys array.
$keys = [];

// Add weather
if($action == 'add') {

  // Get the keys.
  $keys = weather_keys($data);

  // Build callback message string.
  $callback_response = 'OK';

  // Back and abort.
  $keys[] = [
    button(getTranslation('back'), ['pokedex_edit_pokemon', 'p' => $pokedex_id]),
    button(getTranslation('abort'), 'exit')
  ];

  // Set the message.
  $msg = getTranslation('raid_boss') . ': <b>' . get_local_pokemon_name($dex_id, $dex_form) . ' (#' . $dex_id . ')</b>' . CR;
  $msg .= getTranslation('pokedex_current_weather') . ' ' . get_weather_icons($old_weather) . CR . CR;
  $msg .= '<b>' . getTranslation('pokedex_new_weather') . '</b> ' . get_weather_icons($new_weather);

// Save weather to database
} else if($action == 'save') {
  // Update weather of pokemon.
  $rs = my_query('
      UPDATE  pokemon
      SET     weather = ?
      WHERE   pokedex_id = ?
      AND     pokemon_form_id = ?
      ', [$new_weather, $dex_id, $dex_form]
    );

  // Back to pokemon and done keys.
  $keys[0][0] = button(getTranslation('back') . ' (' . get_local_pokemon_name($dex_id, $dex_form) . ')', ['pokedex_edit_pokemon', 'p' => $pokedex_id]);
  $keys[0][1] = button(getTranslation('abort'), ['exit', 'd' => '1']);

  // Build callback message string.
  $callback_response = getTranslation('pokemon_saved') . ' ' . get_local_pokemon_name($dex_id, $dex_form);

  // Set the message.
  $msg = getTranslation('pokemon_saved') . CR;
  $msg .= '<b>' . get_local_pokemon_name($dex_id, $dex_form) . ' (#' . $dex_id . ')</b>' . CR . CR;
  $msg .= getTranslation('pokedex_weather') . ':' . CR;
  $msg .= '<b>' . get_weather_icons($new_weather) . '</b>';
}

// Telegram JSON array.
$tg_json = array();

// Answer callback.
$tg_json[] = answerCallbackQuery($update['callback_query']['id'], $callback_response, true);

// Edit message.
$tg_json[] = edit_message($update, $msg, $keys, false, true);

// Telegram multicurl request.
curl_json_multi_request($tg_json);
