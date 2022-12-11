<?php
/**
 * Pokedex edit pokemon keys.
 * @param $limit
 * @return array
 */
function edit_pokedex_keys($limit)
{
  // Number of entries to display at once.
  $entries = 10;

  // Number of entries to skip with skip-back and skip-next buttons
  $skip = 50;

  // Init empty keys array.
  $pokemonKeys = [];

  // Get all pokemon from database
  $rs = my_query('
    SELECT    pokedex_id, pokemon_form_id
    FROM      pokemon
    ORDER BY  pokedex_id, pokemon_form_name != \'normal\', pokemon_form_name
    LIMIT     ' . $limit . ',' . $entries
  );

  // Number of entries
  $cnt = my_query('
    SELECT    COUNT(*) AS count
    FROM      pokemon
  ');

  // Number of database entries found.
  $sum = $cnt->fetch();
  $count = $sum['count'];

  // List users / moderators
  while ($mon = $rs->fetch()) {
    $pokemon_name = get_local_pokemon_name($mon['pokedex_id'], $mon['pokemon_form_id']);
    $pokemonKeys[][] = [
      'text'          => $mon['pokedex_id'] . SP . $pokemon_name,
      'callback_data' => $mon['pokedex_id'] . '-' . $mon['pokemon_form_id'] . ':pokedex_edit_pokemon:0'
    ];
  }

  // Empty backs and next keys
  $keys_back = [];
  $keys_next = [];

  // Add back key.
  if ($limit > 0) {
    $new_limit = $limit - $entries;
    $keys_back[0][] = [
      'text' => getTranslation('back') . ' (-' . $entries . ')',
      'callback_data' => formatCallbackData(['pokedex', 'l' => $new_limit])
    ];
  }

  // Add skip back key.
  if ($limit - $skip > 0) {
    $new_limit = $limit - $skip - $entries;
    $keys_back[0][] = [
      'text' => getTranslation('back') . ' (-' . $skip . ')',
      'callback_data' => formatCallbackData(['pokedex', 'l' => $new_limit])
    ];
  }

  // Add next key.
  if (($limit + $entries) < $count) {
    $new_limit = $limit + $entries;
    $keys_next[0][] = [
      'text' => getTranslation('next') . ' (+' . $entries . ')',
      'callback_data' => formatCallbackData(['pokedex', 'l' => $new_limit])
    ];
  }

  // Add skip next key.
  if (($limit + $skip + $entries) < $count) {
    $new_limit = $limit + $skip + $entries;
    $keys_next[0][] = [
      'text' => getTranslation('next') . ' (+' . $skip . ')',
      'callback_data' => formatCallbackData(['pokedex', 'l' => $new_limit])
    ];
  }

  // Get the inline key array.
  $keys = array_merge($keys_back, $pokemonKeys, $keys_next);
  $keys[][] = [
    'text' => getTranslation('abort'),
    'callback_data' => 'exit'
  ];

  return $keys;
}
