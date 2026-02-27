<?php
// Write to log.
debug_log('POKEDEX()');
require_once(LOGIC_PATH . '/get_pokemon_id_by_name.php');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
$botUser->accessCheck('pokedex');

// Get pokemon name or dex id.
$pokemon = trim(substr($update['message']['text'], 8));

// Init empty keys array.
$keys = [];

// Check if we recived pokemon name or dex id.
if(!empty($pokemon)) {
  // Pokedex_id received?
  if(is_numeric($pokemon)) {
    $pokedex_id = $pokemon;
  // Pokemon name received?
  } else {
    $pokemon_name_form = get_pokemon_id_by_name($pokemon);
    $pokedex_id = $pokemon_name_form[0];
  }
  $query = my_query('SELECT pokedex_id, pokemon_form_id FROM pokemon WHERE pokedex_id = :pokedex_id', [':pokedex_id' => $pokedex_id]);
  while ($pokemon = $query->fetch()) {
    $keys[][] = button(get_local_pokemon_name($pokemon['pokedex_id'], $pokemon['pokemon_form_id']), ['pokedex_edit_pokemon', 'p' => $pokemon['pokedex_id'] . '-' . $pokemon['pokemon_form_id']]);
  }
  // Set message.
  $msg = '<b>' . getTranslation('pokedex_edit_pokemon') . '</b>';
}

if(count($keys) == 0 ) {
  $query = my_query('SELECT id FROM pokemon WHERE pokedex_id = 9999 and pokemon_form_id = 0'); // A simple check to see if pokemon table has all the necessary data in it
  if($query->rowCount() > 0) {
    // Create keys array.
    $keys[][] = button(getTranslation('pokedex_raid_pokemon'), 'pokedex_list_raids');
    $keys[][] = button(getTranslation('edit_pokemon'), 'pokedex');
    $keys[][] = button(getTranslation('disable_raid_level'), 'pokedex_disable_raids');
    $keys[][] = button(getTranslation('import'), 'pokedex_import');
  }
  $keys[][] = button(getTranslation('update_pokemon_table'), 'getdb');
  // Set message.
  $msg = '<b>' . getTranslation('pokedex_start') . ':</b>';
}
$keys[][] = button(getTranslation('abort'), 'exit');

// Send message.
send_message(create_chat_object([$update['message']['chat']['id']]), $msg, $keys, ['reply_markup' => ['selective' => true, 'one_time_keyboard' => true]]);
