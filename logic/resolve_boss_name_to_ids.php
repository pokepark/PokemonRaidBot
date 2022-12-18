<?php
require_once(LOGIC_PATH . '/get_pokemon_id_by_name.php');

function resolve_boss_name_to_ids($pokemon_name) {
  global $pokebattler_pokemon_map;
  if (array_key_exists($pokemon_name, $pokebattler_pokemon_map))
    $pokemon_name = $pokebattler_pokemon_map[$pokemon_name];
  // Name and form.
  $name = $pokemon_name;
  $form = 'normal';
  // Pokemon name ending with "_FORM" ?
  if (preg_match('/(MEGA|MEGA_Y|MEGA_X|FORM)$/', $pokemon_name)) {
    debug_log('Pokemon with a special form received: ' . $pokemon_name);
    // Remove "_FORM"
    $pokemon = str_replace('_FORM', '', $pokemon_name);

    // Get pokemon name and form.
    [$name, $form] = explode("_", $pokemon, 2);
  }
  // Get ID and form name used internally.
  debug_log('Getting dex id and form for pokemon ' . $name . ' with form ' . $form);
  return get_pokemon_id_by_name($name . ' ' . $form, true);
}
