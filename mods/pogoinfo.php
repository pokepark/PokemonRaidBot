<?php
// Write to log.
debug_log('pogoinfo()');
require_once(LOGIC_PATH . '/curl_get_contents.php');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
$botUser->accessCheck('pokedex');

// Levels available for import
$levels = array('6', '5', '3', '1');
$id = $data['rl'] ?? 0;
$action = $data['a'] ?? '';

// Raid level selection
if($id == 0) {
  // Set message.
  $msg = '<b>' . getTranslation('import') . SP . '(ccev pogoinfo)' . '</b>' . CR . CR;
  $msg .= '<b>' . getTranslation('select_raid_level') . ':</b>';

  // Init empty keys array.
  $keys = [];

  // All raid level keys.
  $keys[][] = array(
    'text'          => getTranslation('pokedex_all_raid_level'),
    'callback_data' => formatCallbackData(['pogoinfo', 'rl' => RAID_LEVEL_ALL])
  );

  // Add key for each raid level
  foreach($levels as $l) {
    $keys[][] = array(
      'text'          => getTranslation($l . 'stars'),
      'callback_data' => formatCallbackData(['pogoinfo', 'rl' => $l])
    );
  }
  $keys[][] = array(
    'text'          => getTranslation('1stars') . ' & ' . getTranslation('3stars'),
    'callback_data' => formatCallbackData(['pogoinfo', 'rl' => '1,3'])
  );

  // Add back and abort buttons
  $keys[] = [
    [
      'text'          => getTranslation('back'),
      'callback_data' => 'pokedex_import'
    ],
    [
      'text'          => getTranslation('abort'),
      'callback_data' => 'exit'
    ]
  ];

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
  exit;
}
// Set message and init message to exclude raid bosses.
$msg = '<b>' . getTranslation('import') . SP . '(ccev pogoinfo)' . '</b>' . CR . CR;
$ex_msg = '';

// Get pogoinfo data.
debug_log('Getting raid bosses from pogoinfo repository now...');
$link = 'https://raw.githubusercontent.com/ccev/pogoinfo/v2/active/raids.json';
$raiddata = curl_get_contents($link);
$raiddata = json_decode($raiddata,true);

// All raid levels?
if($id == RAID_LEVEL_ALL) {
  $get_levels = $levels;
  $clear = "'6','5','3','1'";
} else {
  $get_levels = explode(",", $id);
  $clear = $id;
}

// New request
$exclusions = isset($data['e']) ? explode('#', $data['e']) : [];
if(!empty($exclusions[0])) {
  debug_log('Excluded raid bosses: ' . implode(', ', $exclusions));
}

// Clear old raid bosses.
if($action == 's') {
  require_once(LOGIC_PATH . '/disable_raid_level.php');
  debug_log('Disabling old raid bosses for levels: '. $clear);
  disable_raid_level($clear);
}

// Raid tier array
debug_log('Processing the following raid levels:');
debug_log($get_levels);

// Process raid tier(s)
debug_log('Processing received ccev pogoinfo raid bosses for each raid level');
foreach($raiddata as $tier => $tier_pokemon) {
  // Process raid level?
  if(!in_array($tier,$get_levels)) {
    continue;
  }
  // Raid level and message.
  $msg .= '<b>' . getTranslation('pokedex_raid_level') . SP . $tier . ':</b>' . CR;

  // Count raid bosses and add raid egg later if 2 or more bosses.
  $bosscount = 0;

  // Get raid bosses for each raid level.
  foreach($tier_pokemon as $raid_id_form) {
    if(!isset($raid_id_form['id'])) continue;
    $dex_id = $raid_id_form['id'];
    $dex_form = 0;
    if(isset($raid_id_form['temp_evolution_id'])) {
      $dex_form = '-'.$raid_id_form['temp_evolution_id'];
    }elseif(isset($raid_id_form['form'])) {
      $dex_form = $raid_id_form['form'];
    }else {
      // If no form id is provided, let's check our db for normal form
      $query_form_id = my_query('SELECT pokemon_form_id FROM pokemon WHERE pokedex_id = ? and pokemon_form_name = \'normal\' LIMIT 1', [$dex_id]);
      if($query_form_id->rowCount() == 0) {
        // If normal form doesn't exist in our db, use the smallest form id as a fallback
        $query_form_id = my_query('SELECT min(pokemon_form_id) as pokemon_form_id FROM pokemon WHERE pokedex_id = ? LIMIT 1', [$dex_id]);
      }
      $result = $query_form_id->fetch();
      $dex_form = $result['pokemon_form_id'];
    }

    $pokemon_arg = $dex_id . $dex_form;

    // Make sure we received a valid dex id.
    if(!is_numeric($dex_id) || $dex_id == 0) {
      info_log('Failed to get a valid pokemon dex id: '. $dex_id .' Continuing with next raid boss...');
      continue;
    }

    // Save to database?
    if($action == 's') {
      // Update raid level of pokemon
      my_query('
        INSERT INTO raid_bosses (pokedex_id, pokemon_form_id, raid_level)
        VALUES (?, ?, ?)
        ', [$dex_id, $dex_form, $tier]
      );
    }

    // Get ID and form name used internally.
    $local_pokemon = get_local_pokemon_name($dex_id, $dex_form);
    debug_log('Got this pokemon dex id: ' . $dex_id);
    debug_log('Got this pokemon dex form: ' . $dex_form);
    debug_log('Got this local pokemon name and form: ' . $local_pokemon);

    // Exclude pokemon?
    if(in_array($pokemon_arg, $exclusions)) {
      // Add pokemon to exclude message.
      $ex_msg .= $local_pokemon . SP . '(#' . $dex_id . ')' . CR;

    } else {
      // Add pokemon to message.
      $msg .= $local_pokemon . SP . '(#' . $dex_id . ')' . CR;

      // Counter.
      $bosscount = $bosscount + 1;

      // Are 3 raid bosses already selected?
      if(count($exclusions) == 3) continue;

      $keyText = $local_pokemon;
      if($id == RAID_LEVEL_ALL) {
        $keyText = '[' . ($tier) . ']' . SP . $local_pokemon;
      }
      $e = $exclusions;
      $e[] = $pokemon_arg;
      $keyAction = ($action == 's') ?
        ['pokedex_edit_pokemon', 'id' => $dex_id . "-" . $dex_form, 'arg' => ''] :
        ['pogoinfo', 'rl' => $id, 'e' => implode('#', $e)];
      // Add key
      $keys[] = array(
        'text'          => $keyText,
        'callback_data' => formatCallbackData($keyAction)
      );
    }
  }
  $msg .= CR;
}

// Get the inline key array.
$keys = inline_key_array($keys, 2);

// Saved raid bosses?
if($action == 's') {
  $msg .= '<b>' . getTranslation('import_done') . '</b>' . CR;
  $msg .= CR . '<b>' . getTranslation('pokedex_edit_pokemon') . '</b>';

  // Abort button.
  $nav_keys = universal_key([], 0, 'exit', 0, getTranslation('done'));

// User is still on the import.
} else {
  $msg .= '<b>' . getTranslation('excluded_raid_bosses') . '</b>' . CR;
  $msg .= (empty($ex_msg) ? (getTranslation('none') . CR) : $ex_msg) . CR;

  // Import or select more pokemon to exclude?
  if(!isset($exclusions[2])) {
    $msg .= '<b>' . getTranslation('exclude_raid_boss_or_import') . ':</b>';
  } else {
    $msg .= '<b>' . getTranslation('import_raid_bosses') . '</b>';
  }

  // Navigation keys.
  $nav_keys = [];

  // Back button.
  $nav_keys[] = array(
    'text'          => getTranslation('back'),
    'callback_data' => 'pogoinfo'
  );

  // Save button.
  $nav_keys[] = array(
    'text'          => EMOJI_DISK,
    'callback_data' => formatCallbackData(['pogoinfo', 'rl' => $id, 'a' => 's', 'e' => implode('#', $exclusions)])
  );

  // Reset button.
  if(isset($exclusions[0])) {
    $nav_keys[] = array(
      'text'          => getTranslation('reset'),
      'callback_data' => formatCallbackData(['pogoinfo', 'rl' => $id])
    );
  }

  // Abort button.
  $nav_keys[] = array(
    'text'          => getTranslation('abort'),
    'callback_data' => '0:exit:0'
  );

  // Get the inline key array and merge keys.
  $nav_keys = inline_key_array($nav_keys, 2);
}
$keys = array_merge($keys, $nav_keys);

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
