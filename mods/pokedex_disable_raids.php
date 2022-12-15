<?php
// Write to log.
debug_log('pokedex_disable_raids()');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
$botUser->accessCheck('pokedex');

// Get raid levels.
$id = $data['rl'] ?? 0;

// Get argument.
$arg = $data['a'] ?? 0;

// Specify raid levels.
$levels = str_split(RAID_LEVEL_ALL);

// All raid levels?
if($id == RAID_LEVEL_ALL) {
  $clear = "'" . implode("','", $levels) . "'";
} else {
  $clear = "'" . $id . "'";
}

// Raid level selection
if($arg == 0) {
  // Set message.
  $msg = '<b>' . getTranslation('disable_raid_level') . ':</b>';

  // Init empty keys array.
  $keys = [];

  // All raid level keys.
  $keys[][] = button(getTranslation('pokedex_all_raid_level'), ['pokedex_disable_raids', 'rl' => RAID_LEVEL_ALL, 'a' => 1]);

  // Add key for each raid level
  foreach($levels as $l) {
    $keys[][] = button(getTranslation($l . 'stars'), ['pokedex_disable_raids', 'rl' => $l, 'a' => 1]);
  }

  // Add abort button
  $keys[][] = button(getTranslation('abort'), 'exit');

// Confirmation to disable raid level
} else if($arg == 1) {
  // Get all pokemon with raid levels from database.
  $rs = my_query('
    SELECT  pokemon.pokedex_id, pokemon.pokemon_form_name, pokemon.pokemon_form_id, raid_bosses.raid_level
    FROM    raid_bosses
    LEFT JOIN pokemon
    ON    pokemon.pokedex_id = raid_bosses.pokedex_id
    AND     pokemon.pokemon_form_id = raid_bosses.pokemon_form_id
    WHERE   raid_bosses.raid_level IN (' . $clear . ')
    AND     raid_bosses.scheduled = 0
    ORDER BY  raid_level, pokedex_id, pokemon_form_name != \'normal\', pokemon_form_name
  ');

  // Init empty keys array.
  $keys = [];

  // Init message and previous.
  $msg = '<b>' . getTranslation('disable_raid_level') . '?</b>' . CR;
  $previous = '';

  // Build the message
  while ($pokemon = $rs->fetch()) {
    // Set current level
    $current = $pokemon['raid_level'];

    // Add header for each raid level
    if($previous != $current) {
      $msg .=  CR . '<b>' . getTranslation($pokemon['raid_level'] . 'stars') . ':</b>' . CR ;
    }

    // Add pokemon with id and name.
    $dex_id = $pokemon['pokedex_id'];
    $poke_name = get_local_pokemon_name($dex_id, $pokemon['pokemon_form_id']);
    $msg .= $poke_name . ' (#' . $dex_id . ')' . CR;

    // Prepare next run.
    $previous = $current;
  }

  // Disable.
  $keys[] = button(getTranslation('yes'), ['pokedex_disable_raids', 'rl' => $id, 'a' => 2]);

  // Abort.
  $keys[] = button(getTranslation('no'), 'exit');

  // Inline keys array.
  $keys = inline_key_array($keys, 2);

// Disable raid level
} else if($arg == 2) {
  require_once(LOGIC_PATH . '/disable_raid_level.php');
  debug_log('Disabling old raid bosses for levels: '. $clear);
  disable_raid_level($clear);

  // Message.
  $msg = '<b>' . getTranslation('disabled_raid_level') . ':</b>' . CR;

  // All levels
  if($id == RAID_LEVEL_ALL) {
    foreach($levels as $lv) {
      $msg .= getTranslation($lv . 'stars') . CR;
    }

   // Specific level
   } else {
    $msg .= getTranslation($id . 'stars');
   }

   // Empty keys.
   $keys = [];
}

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
