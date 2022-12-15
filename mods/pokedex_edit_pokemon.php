<?php
// Write to log.
debug_log('pokedex_edit_pokemon()');
require_once(LOGIC_PATH . '/get_formatted_pokemon_cp.php');
require_once(LOGIC_PATH . '/get_pokemon_info.php');
require_once(LOGIC_PATH . '/get_weather_icons.php');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
$botUser->accessCheck('pokedex');
// Set the id.
$poke_id_form = $data['p'];
[$pokedex_id, $pokemon_form_id] = explode('-',$poke_id_form,2);

// Set the message.
$pokemon = get_pokemon_info($pokedex_id, $pokemon_form_id);
$poke_cp = get_formatted_pokemon_cp($pokemon);
$msg = getTranslation('raid_boss') . ': <b>' . get_local_pokemon_name($pokedex_id, $pokemon_form_id) . ' (#' . $pokedex_id . ')</b>' . CR . CR;
$msg .= getTranslation('pokedex_raid_level') . ': ' . getTranslation($pokemon['raid_level'] . 'stars') . CR;
$msg .= (empty($poke_cp)) ? (getTranslation('pokedex_cp') . CR) : $poke_cp . CR;
$msg .= getTranslation('pokedex_weather') . ': ' . get_weather_icons($pokemon['weather']) . CR;
$msg .= (($pokemon['shiny'] == 1) ? (EMOJI_SHINY . SP . getTranslation('shiny')) : (getTranslation('not_shiny'))) . CR . CR;
$msg .= '<b>' . getTranslation('pokedex_select_action') . '</b>';

// Create keys array.
$keys[][] = button(getTranslation('pokedex_raid_level'), ['pokedex_set_raid_level', 'p' => $poke_id_form]);

// Raid-Egg? Hide specific options!
if(!in_array($pokedex_id, $GLOBALS['eggs'])) {
  $keys[][] = button(getTranslation('pokedex_min_cp'), ['pokedex_set_cp', 'p' => $poke_id_form, 'a' => 'add', 'l' => 20, 't' => 'min']);
  $keys[][] = button(getTranslation('pokedex_max_cp'), ['pokedex_set_cp', 'p' => $poke_id_form, 'a' => 'add', 'l' => 20, 't' => 'max']);
  $keys[][] = button(getTranslation('pokedex_min_weather_cp'), ['pokedex_set_cp', 'p' => $poke_id_form, 'a' => 'add', 'l' => 25, 't' => 'min']);
  $keys[][] = button(getTranslation('pokedex_max_weather_cp'), ['pokedex_set_cp', 'p' => $poke_id_form, 'a' => 'add', 'l' => 25, 't' => 'max']);
  $keys[][] = button(getTranslation('pokedex_weather'), ['pokedex_set_weather', 'p' => $poke_id_form]);
  $keys[][] = button(getTranslation('shiny'), ['pokedex_set_shiny', 'p' => $poke_id_form]);
}

// Back and abort.
$keys[] = [
  button(getTranslation('back'), 'pokedex'),
  button(getTranslation('abort'), 'exit')
];

// Build callback message string.
$callback_response = 'OK';

// Telegram JSON array.
$tg_json = array();

// Answer callback.
$tg_json[] = answerCallbackQuery($update['callback_query']['id'], $callback_response, true);

// Edit message.
$tg_json[] = edit_message($update, $msg, $keys, false, true);

// Telegram multicurl request.
curl_json_multi_request($tg_json);
