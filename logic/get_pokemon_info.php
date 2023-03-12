<?php
/**
 * Get pokemon info as formatted string.
 * @param $pokemon_id
 * @param $pokemon_form_id
 * @return array
 */
function get_pokemon_info($pokedex_id, $pokemon_form_id)
{
  $query = my_query('
    SELECT  id, min_cp, max_cp, min_weather_cp, max_weather_cp, weather, shiny,
            (SELECT    raid_level
            FROM      raid_bosses
            WHERE     pokedex_id = :pokedex_id
            AND       pokemon_form_id = :pokemon_form_id
            AND       scheduled = 0 LIMIT 1) as raid_level
    FROM    pokemon
    WHERE   pokedex_id = :pokedex_id
    AND     pokemon_form_id = :pokemon_form_id
    LIMIT   1
    ', [
        'pokedex_id' =>   $pokedex_id,
        'pokemon_form_id' => $pokemon_form_id
      ]);
  $result = $query->fetch();
  if($result['raid_level'] == NULL) $result['raid_level'] = 0;
  return $result;
}
