<?php
/**
 * Pokemon keys.
 * @param array $callbackData
 * @param int $raid_level
 * @param string $action
 * @param int|bool $event_id
 * @return array
 */
function pokemon_keys($callbackData, $raid_level, $action, $event_id = false)
{
  global $config;
  // Init empty keys array.
  $keys = [];

  $time_now = dt2time(utcnow(), 'Y-m-d H:i');

  $egg_id = '999' . $raid_level;

  // Get pokemon from database
  $rs = my_query('
    SELECT  pokemon.id, pokemon.pokedex_id, pokemon.pokemon_form_id
    FROM    raid_bosses
    LEFT    JOIN pokemon
    ON      pokemon.pokedex_id = raid_bosses.pokedex_id
    AND     pokemon.pokemon_form_id = raid_bosses.pokemon_form_id
    WHERE   raid_bosses.raid_level = :raidLevel
    AND     (
              DATE_SUB(\'' . $time_now . '\', INTERVAL '.$config->RAID_EGG_DURATION.' MINUTE) between date_start and date_end
          OR  DATE_ADD(\'' . $time_now . '\', INTERVAL '.$config->RAID_DURATION.' MINUTE) between date_start and date_end
    )
    UNION
      SELECT  id, pokedex_id, pokemon_form_id
      FROM  pokemon
      WHERE   pokedex_id = :eggId
    ORDER BY  pokedex_id
    ', ['raidLevel' => $raid_level, 'eggId' => $egg_id]
  );
  // Add key for each raid level
  $callbackData[0] = $action;
  while ($pokemon = $rs->fetch()) {
    $callbackData['p'] = $pokemon['id'];
    $keys[] = button(get_local_pokemon_name($pokemon['pokedex_id'], $pokemon['pokemon_form_id']), $callbackData);
  }

  // Get the inline key array.
  $keys = inline_key_array($keys, 1);

  return $keys;
}
