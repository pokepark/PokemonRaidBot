<?php
/**
 * Get local name of pokemon.
 * @param $pokemon_id
 * @param $pokemon_form_id
 * @param $override_language
 * @return string
 */
function get_local_pokemon_name($pokemon_id, $pokemon_form_id, $override_language = false)
{
  $q = my_query('SELECT pokemon_name, pokemon_form_name FROM pokemon WHERE pokedex_id = ? AND pokemon_form_id = ?', [$pokemon_id, $pokemon_form_id]);
  $res = $q->fetch();
  $pokemon_form_name = $res['pokemon_form_name'] ?? 'normal';

  debug_log('Pokemon_form: ' . $pokemon_form_name);

  // Get translation type
  $getTypeTranslation = ($override_language == true) ? 'getPublicTranslation' : 'getTranslation';

  // Init pokemon name and define fake pokedex ids used for raid eggs
  $pokemon_name = '';
  $eggs = $GLOBALS['eggs'];

  // Get eggs from normal translation.
  $pokemon_name = (in_array($pokemon_id, $eggs)) ? $getTypeTranslation('egg_' . substr($pokemon_id, -1)) : $getTypeTranslation('pokemon_id_' . $pokemon_id);

  if ($pokemon_form_name != 'normal') {
    $pokemon_form_name = $getTypeTranslation('pokemon_form_' . $pokemon_form_name);
  }
  // If we didn't find Pokemon name or form name from translation files, use the name from database as fallback
  if(empty($pokemon_name) or empty($pokemon_form_name)) {
    // Pokemon name
    $pokemon_name = (empty($pokemon_name) ? $res['pokemon_name'] : $pokemon_name);
    // Pokemon form
    if(empty($pokemon_form_name) && $res['pokemon_form_name'] != 'normal') {
        $pokemon_form_name = ucfirst(str_replace('_',' ',$res['pokemon_form_name']));
    }
  }
  return $pokemon_name . ($pokemon_form_name != "normal" ? " " . $pokemon_form_name : "");
}
