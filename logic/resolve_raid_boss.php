<?php
/**
 * Get raid data.
 * @param $pokemon
 * @param $pokemon_form
 * @param $spawn
 * @param $raid_level
 * @return array
 */
function resolve_raid_boss($pokemon, $pokemon_form, $spawn, $raid_level) {
  if($pokemon == 0) {
    $tz_diff = tz_diff();
    $query = my_query('
      SELECT  DISTINCT  pokedex_id, pokemon_form_id
      FROM        raid_bosses
      WHERE       raid_level = :raidLevel
      AND         scheduled = 1
      AND         convert_tz(:spawn, "+00:00", :tzDiff) BETWEEN date_start AND date_end
    ', ['raidLevel' => $raid_level, 'spawn' => $spawn, 'tzDiff' => $tz_diff]);
    // Return egg
    $pokemon_id = '999'.$raid_level;
    $pokemon_form_id = 0;
    if($query->rowCount() == 1) {
      $row = $query->fetch();
      // Return active boss
      $pokemon_id = $row['pokedex_id'];
      $pokemon_form_id = $row['pokemon_form_id'];
    }
    return ['pokedex_id' => $pokemon_id, 'pokemon_form_id' => $pokemon_form_id];
  }
  $pokemon_id = $pokemon;
  $pokemon_form_id = $pokemon_form;
  if($pokemon_form == 0) {
    // If pokemon_form is 0 (often received from webhook), resolve the form id of normal form from our database
    $form_query = my_query('
      SELECT  pokemon_form_id
      FROM  pokemon
      WHERE   pokedex_id = ?
      AND   pokemon_form_name = \'normal\'
      LIMIT   1', [$pokemon]);
    $pokemon_form_id = $form_query->fetch()['pokemon_form_id'];
  }
  return ['pokedex_id' => $pokemon_id, 'pokemon_form_id' => $pokemon_form_id];
}
