<?php
/**
 * Raid edit start keys.
 * @param array $callbackData
 * @param array $admin_access, [ex_raid, event_raid]
 * @param int|bool $event_id
 * @return array
 */
function raid_edit_raidlevel_keys($callbackData, $admin_access = [false,false], $event = false)
{
  global $config;

  if($event === false) {
    // Set event ID to null if no event was selected
    $query_event = 'AND raid_bosses.raid_level != \'X\'';
  }else {
    if($admin_access[0] === true) $query_event = '';
    else $query_event = 'AND raid_bosses.raid_level != \'X\'';
  }
  $query_counts = '
    SELECT  raid_level, COUNT(*) AS raid_level_count
    FROM    raid_bosses
    WHERE   (
          DATE_SUB(UTC_TIMESTAMP(), INTERVAL '.$config->RAID_EGG_DURATION.' MINUTE) between date_start and date_end
      OR  DATE_ADD(UTC_TIMESTAMP(), INTERVAL '.$config->RAID_DURATION.' MINUTE) between date_start and date_end
      )
      '.$query_event.'
    GROUP BY  raid_bosses.raid_level
    ORDER BY  FIELD(raid_bosses.raid_level, \'9\', \'8\', \'7\', \'6\', \'5\', \'4\', \'3\', \'2\', \'1\', \'X\')
  ';
  // Get all raid levels from database
  $rs_counts = my_query($query_counts);

  // Init empty keys array.
  $keys = [];

  // Add key for each raid level
  $buttonData = $callbackData;
  while ($level = $rs_counts->fetch()) {
    // Add key for pokemon if we have just 1 pokemon for a level
    if($level['raid_level_count'] != 1) {
      // Raid level and action
      $buttonData[0] = 'edit_pokemon';
      $buttonData['rl'] = $level['raid_level'];
      // Add key for raid level
      $keys[] = array(
        'text'          => getTranslation($level['raid_level'] . 'stars'),
        'callback_data' => formatCallbackData($buttonData)
      );
      continue;
    }
    $query_mon = my_query('
      SELECT  pokemon.id, pokemon.pokedex_id, pokemon.pokemon_form_id
      FROM    raid_bosses
      LEFT JOIN pokemon
      ON      pokemon.pokedex_id = raid_bosses.pokedex_id
      AND     pokemon.pokemon_form_id = raid_bosses.pokemon_form_id
      WHERE   (
              DATE_SUB(UTC_TIMESTAMP(), INTERVAL '.$config->RAID_EGG_DURATION.' MINUTE) between date_start and date_end
          OR  DATE_ADD(UTC_TIMESTAMP(), INTERVAL '.$config->RAID_DURATION.' MINUTE) between date_start and date_end
      )
      AND     raid_level = ?
      '.$query_event.'
      LIMIT 1
      ', [$level['raid_level']]
    );
    $pokemon = $query_mon->fetch();
    $buttonData[0] = 'edit_starttime';
    $buttonData['rl'] = $level['raid_level'];
    $buttonData['p'] = $pokemon['id'];
    // Add key for pokemon
    $keys[] = array(
      'text'          => get_local_pokemon_name($pokemon['pokedex_id'], $pokemon['pokemon_form_id']),
      'callback_data' => formatCallbackData($buttonData)
    );
    unset($buttonData['p']);
  }
  // Add key for raid event if user allowed to create event raids
  if(($admin_access[1] === true or $admin_access[0] === true) && $event === false) {
    $eventData = $callbackData;
    $eventData[0] = 'edit_event';
    $keys[] = array(
      'text'          => getTranslation('event'),
      'callback_data' => formatCallbackData($eventData)
    );
  }

  // Get the inline key array.
  $keys = inline_key_array($keys, 3);

  return $keys;
}
