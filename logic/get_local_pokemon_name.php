<?php
/**
 * Get local name of pokemon.
 * @param int $pokemon_id
 * @param int $pokemon_form_id
 * @param string $language
 * @return string
 */
function get_local_pokemon_name($pokemon_id, $pokemon_form_id, $language = null)
{
  global $config, $botUser;
  $q = my_query('SELECT pokemon_name, pokemon_form_name FROM pokemon WHERE pokedex_id = ? AND pokemon_form_id = ?', [$pokemon_id, $pokemon_form_id]);
  $res = $q->fetch();
  $pokemon_form_name = $res['pokemon_form_name'] ?? 'normal';

  debug_log('Pokemon_form: ' . $pokemon_form_name);
  if($language === null) $language = $botUser->userLanguage;

  // Init pokemon name and define fake pokedex ids used for raid eggs
  $pokemon_name = '';

  // Get eggs from normal translation.
  $pokemon_name = (in_array($pokemon_id, EGGS)) ?
    getTranslation('egg_' . str_replace('999', '', $pokemon_id), $language) :
    getTranslation('pokemon_id_' . $pokemon_id, $language);

  $skipFallback = false;
  if ($pokemon_form_name != 'normal') {
    $pokemon_form_name = getTranslation('pokemon_form_' . $pokemon_form_id, $language);
    // Use only form name if form name contains Pokemon name
    // e.g. Black Kyurem, Frost Rotom
    if(strpos($pokemon_form_name, $pokemon_name, 0)) {
      $pokemon_name = $pokemon_form_name;
      $pokemon_form_name = '';
      $skipFallback = true;
    }
  }
  // If we didn't find Pokemon name or form name from translation files, use the name from database as fallback
  if(empty($pokemon_name) or empty($pokemon_form_name) && !$skipFallback) {
    // Pokemon name
    $pokemon_name = (empty($pokemon_name) ? $res['pokemon_name'] : $pokemon_name);
    // Pokemon form
    if(empty($pokemon_form_name) && $res['pokemon_form_name'] != 'normal') {
      $pokemon_form_name = ucfirst(str_replace('_',' ',$res['pokemon_form_name']));
    }
  }
  return $pokemon_name . ($pokemon_form_name != "normal" ? " " . $pokemon_form_name : "");
}
