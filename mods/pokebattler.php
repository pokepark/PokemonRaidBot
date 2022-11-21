<?php
// Write to log.
debug_log('pokebattler()');
require_once(LOGIC_PATH . '/curl_get_contents.php');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
$botUser->accessCheck($update, 'pokedex');
include(LOGIC_PATH . '/resolve_boss_name_to_ids.php');

// Get raid levels
$id = $data['id'];

// Exclude pokemon
$arg = $data['arg'];

// Raid level selection
if($id == 0) {
  // Set message.
  $msg = '<b>' . getTranslation('import') . SP . '(Pokebattler)' . '</b>' . CR . CR;
  $msg .= '<b>' . getTranslation('select_raid_level') . ':</b>';

  // Init empty keys array.
  $keys = [];

  // All raid level keys.
  $keys[][] = array(
    'text'          => getTranslation('pokedex_all_raid_level'),
    'callback_data' => implode(",", $pokebattler_levels) . ':pokebattler:ex#0,0,0'
  );

  // Add key for each raid level
  foreach($pokebattler_levels as $l) {
    $keys[][] = array(
      'text'          => getTranslation($l . 'stars'),
      'callback_data' => $l . ':pokebattler:ex#0,0,0'
    );
  }
  $keys[][] = array(
    'text'          => getTranslation('1stars') . ' & ' . getTranslation('3stars'),
    'callback_data' => '3,1:pokebattler:ex#0,0,0'
  );

  // Add back and abort buttons
  $keys[] = [
    [
      'text'          => getTranslation('back'),
      'callback_data' => '0:pokedex_import:0'
    ],
    [
      'text'          => getTranslation('abort'),
      'callback_data' => '0:exit:0'
    ]
  ];

} else if($id > 0) {
  // Set message and init message to exclude raid bosses.
  $msg = '<b>' . getTranslation('import') . SP . '(Pokebattler)' . '</b>' . CR . CR;
  $ex_msg = '';

  // Get pokebattler data.
  debug_log('Getting raid bosses from pokebattler.com now...');
  $link = 'https://fight.pokebattler.com/raids';
  $pb_data = curl_get_contents($link);
  $pb_data = json_decode($pb_data,true);

  $get_levels = explode(",", $id);
  $clear = $id;

  // Prefix for exclusion.
  $prefix = 'ex#';

  // New request
  if($arg == 'ex#0,0,0') {
    $poke1 = 0;
    $poke2 = 0;
    $poke3 = 0;

  // Get raid bosses to exclude.
  } else if(strpos($arg, 'ex#') === 0 || strpos($arg, 'save#') === 0) {
    $poke_ids = explode('#', $arg);
    $poke_ids = explode(',', $poke_ids[1]);
    $poke1 = $poke_ids[0];
    $poke2 = $poke_ids[1];
    $poke3 = $poke_ids[2];
    debug_log('Excluded raid boss #1: ' . $poke1);
    debug_log('Excluded raid boss #2: ' . $poke2);
    debug_log('Excluded raid boss #3: ' . $poke3);
  }

  // Clear old raid bosses.
  if(strpos($arg, 'save#') === 0) {
    require_once(LOGIC_PATH . '/disable_raid_level.php');
    debug_log('Disabling old raid bosses for levels: '. $clear);
    disable_raid_level($clear);
  }

  // Init empty keys array.
  $keys = [];

  // Raid tier array
  debug_log('Processing the following raid levels:');
  $raidlevels = array();
  foreach($get_levels as $level) {
    $raidlevels[] = 'RAID_LEVEL_' . $pokebattler_level_map[$level];
  }
  debug_log($raidlevels);
  $levels_processed = [];
  $bosses = [];
  // Process breaking news section
  $now = new DateTime('now', new DateTimeZone($config->TIMEZONE));
  $ph = new dateTimeZone('America/Phoenix');
  foreach($pb_data['breakingNews'] as $news) {
    if($news['type'] == 'RAID_TYPE_RAID') {
      $rl = str_replace('RAID_LEVEL_','', $news['tier']);
      $raid_level_id = array_search($rl, $pokebattler_level_map);
      $starttime = new DateTime("@".substr($news['startDate'],0,10));
      $endtime = new DateTime("@".substr($news['endDate'],0,10));
      $starttime->setTimezone($ph);
      $endtime->setTimezone($ph);

      if(in_array($news['tier'], $raidlevels) && $starttime->getTimestamp() < $now->getTimestamp() && $endtime->getTimestamp() > $now->getTimestamp()) {
        $levels_processed[$raid_level_id] = $news['tier'];
        $dex_id_form = resolve_boss_name_to_ids($news['pokemon']);
        $bosses[$raid_level_id][] = ['id' => $dex_id_form, 'shiny' => $news['shiny']];
      }
    }
  }
  // Process raid tier(s)
  debug_log('Processing received pokebattler raid bosses for each raid level');
  foreach($pb_data['tiers'] as $tier) {
    $rl = str_replace('RAID_LEVEL_','', $tier['tier']);
    $raid_level_id = array_search($rl, $pokebattler_level_map);
    // Skip this raid level if the boss data was already collected from breaking news or raid level doesn't interest us
    if(!in_array($tier['tier'], $raidlevels) or isset($levels_processed[$raid_level_id])) {
      continue;
    }
    // Get raid bosses for each raid level.
    foreach($tier['raids'] as $raid) {
      // Raid level
      if ($raid['id'] == 0) {
        debug_log("Skipping raid boss {$raid['pokemon']} since it has no id, it's likely in the future!");
        continue;
      }
      $dex_id_form = resolve_boss_name_to_ids($raid['pokemon']);
      $bosses[$raid_level_id][] = ['id' => $dex_id_form, 'shiny' => $raid['shiny']];
    }
  }

  foreach($get_levels as $raid_level_id) {
    if($raid_level_id > 5) $raid_level_text = getTranslation($raid_level_id . 'stars_short'); else $raid_level_text = $raid_level_id;
    if(!isset($bosses[$raid_level_id])) continue;
    $msg .= '<b>' . getTranslation('pokedex_raid_level') . SP . $raid_level_text . ':</b>' . CR;
    foreach($bosses[$raid_level_id] as $dex_id_form) {
      [$dex_id, $dex_form] = $dex_id_form['id'];
      $pokemon_arg = $dex_id . (($dex_form != 'normal') ? ('-' . $dex_form) : '-0');
      $local_pokemon = get_local_pokemon_name($dex_id, $dex_form);
      debug_log('Got this pokemon dex id: ' . $dex_id);
      debug_log('Got this pokemon dex form: ' . $dex_form);
      debug_log('Got this local pokemon name and form: ' . $local_pokemon);

      // Make sure we received a valid dex id.
      if(!is_numeric($dex_id) || $dex_id == 0) {
        info_log('Failed to get a valid pokemon dex id: '. $dex_id .' Continuing with next raid boss...');
        continue;
      }

      // Build new arg.
      // Exclude 1 pokemon
      if($poke1 == '0') {
        $new_arg = $prefix . $pokemon_arg . ',0,0';

      // Exclude 2 pokemon
      } else if ($poke1 != '0' && $poke2 == '0') {
        $new_arg = $prefix . $poke1 . ',' . $pokemon_arg . ',0';

      // Exclude 3 pokemon
      } else if ($poke1 != '0' && $poke2 != '0' && $poke3 == '0') {
        $new_arg = $prefix . $poke1 . ',' . $poke2 . ',' . $pokemon_arg;
      }

      // Exclude pokemon?
      if($pokemon_arg == $poke1 || $pokemon_arg == $poke2 || $pokemon_arg == $poke3) {
        // Add pokemon to exclude message.
        $ex_msg .= $local_pokemon . SP . '(#' . $dex_id . ')' . CR;

      } else {
        // Add pokemon to message.
        $msg .= $local_pokemon . SP . '(#' . $dex_id . ')' . CR;

        // Shiny?
        $shiny = 0;
        if($dex_id_form['shiny'] == 'true') {
          $shiny = 1;
        }

        // Save to database?
        if(strpos($arg, 'save#') === 0) {
          // Update raid level of pokemon
          my_query('
              UPDATE  pokemon
              SET     shiny = ?
              WHERE   pokedex_id = ?
              AND     pokemon_form_id = ?
              ', [$shiny, $dex_id, $dex_form]
            );
          my_query('
              INSERT INTO raid_bosses (pokedex_id, pokemon_form_id, raid_level)
              VALUES (?, ?, ?)
              ', [$dex_id, $dex_form, $raid_level_id]
            );
          continue;
        }

        // Are 3 raid bosses already selected?
        if($poke1 == '0' || $poke2 == '0' || $poke3 == '0') {
          // Add key to exclude pokemon from import.
          $button_text_prefix = '';
          if($id == implode(",", $pokebattler_levels)) {
            // Add raid level to pokemon name
            $button_text_prefix = '[' . ($raid_level_text) . ']';
          }
          $keys[] = array(
            'text'          => $button_text_prefix . SP . $local_pokemon,
            'callback_data' => $id . ':pokebattler:' . $new_arg
          );
        }
      }
    }
    $msg .= CR;
  }

  // Get the inline key array.
  $keys = inline_key_array($keys, 2);

  // Saved raid bosses?
  if(strpos($arg, 'save#') === 0) {
    // Get all pokemon with raid levels from database.
    $rs = my_query('
      SELECT  raid_bosses.pokedex_id, raid_bosses.pokemon_form_id, raid_bosses.raid_level
      FROM    raid_bosses
      LEFT JOIN pokemon
      ON    raid_bosses.pokedex_id = pokemon.pokedex_id
      AND     raid_bosses.pokemon_form_id = pokemon.pokemon_form_id
      WHERE   raid_bosses.raid_level IN (' . $clear . ')
      AND     raid_bosses.scheduled = 0
      ORDER BY  raid_bosses.raid_level, raid_bosses.pokedex_id, pokemon.pokemon_form_name != \'normal\', pokemon.pokemon_form_name, raid_bosses.pokemon_form_id
    ');

    // Init empty keys array.
    $keys = [];

    // Init message and previous.
    $msg = '<b>Pokebattler' . SP . '—' . SP . getTranslation('import_done') . '</b>' . CR;
    $previous = '';

    // Build the message
    while ($pokemon = $rs->fetch()) {
      // Set current level
      $current = $pokemon['raid_level'];

      // Add header for each raid level
      if($previous != $current) {
        $msg .= CR . '<b>' . getTranslation($pokemon['raid_level'] . 'stars') . ':</b>' . CR ;
      }

      // Add pokemon with id and name.
      $dex_id = $pokemon['pokedex_id'];
      $pokemon_form_id = $pokemon['pokemon_form_id'];
      $poke_name = get_local_pokemon_name($dex_id, $pokemon_form_id);
      $msg .= $poke_name . ' (#' . $dex_id . ')' . CR;

      // Add button to edit pokemon.
      if($id == RAID_LEVEL_ALL) {
        $keys[] = array(
          'text'          => '[' . $pokemon['raid_level'] . ']' . SP . $poke_name,
          'callback_data' => $dex_id . "-" . $pokemon_form_id . ':pokedex_edit_pokemon:0'
        );
      } else {
        $keys[] = array(
          'text'          => $poke_name,
          'callback_data' => $dex_id . "-" . $pokemon_form_id . ':pokedex_edit_pokemon:0'
        );
      }

      // Prepare next run.
      $previous = $current;
    }

    // Message.
    $msg .= CR . '<b>' . getTranslation('pokedex_edit_pokemon') . '</b>';

    // Inline key array.
    $keys = inline_key_array($keys, 2);

    // Navigation keys.
    $nav_keys = [];

    // Abort button.
    $nav_keys[] = array(
      'text'          => getTranslation('done'),
      'callback_data' => '0:exit:1'
    );

    // Keys.
    $nav_keys = inline_key_array($nav_keys, 1);
    $keys = array_merge($keys, $nav_keys);

  // User is still on the import.
  } else {
    $msg .= '<b>' . getTranslation('excluded_raid_bosses') . '</b>' . CR;
    $msg .= (empty($ex_msg) ? (getTranslation('none') . CR) : $ex_msg) . CR;

    // Import or select more pokemon to exclude?
    if($poke1 == '0' || $poke2 == '0' || $poke3 == '0') {
      $msg .= '<b>' . getTranslation('exclude_raid_boss_or_import') . ':</b>';
    } else {
      $msg .= '<b>' . getTranslation('import_raid_bosses') . '</b>';
    }

    // Navigation keys.
    $nav_keys = [];

    // Back button.
    $nav_keys[] = array(
      'text'          => getTranslation('back'),
      'callback_data' => '0:pokebattler:0'
    );

    // Save button.
    $nav_keys[] = array(
      'text'          => EMOJI_DISK,
      'callback_data' => $id . ':pokebattler:save#' . $poke1 . ',' . $poke2 . ',' . $poke3
    );

    // Reset button.
    if($poke1 != 0) {
      $nav_keys[] = array(
        'text'          => getTranslation('reset'),
        'callback_data' => $id . ':pokebattler:ex#0,0,0'
      );
    }

    // Abort button.
    $nav_keys[] = array(
      'text'          => getTranslation('abort'),
      'callback_data' => '0:exit:0'
    );

    // Get the inline key array and merge keys.
    $nav_keys = inline_key_array($nav_keys, 2);
    $keys = array_merge($keys, $nav_keys);
  }
}

// Callback message string.
$callback_response = 'OK';

// Telegram JSON array.
$tg_json = array();

// Answer callback.
$tg_json[] = answerCallbackQuery($update['callback_query']['id'], $callback_response, true);

// Edit message.
$tg_json[] = edit_message($update, $msg, $keys, false, true);

// Telegram multicurl request.
curl_json_multi_request($tg_json);
