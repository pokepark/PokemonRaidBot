<?php
// Write to log.
debug_log('pokebattler()');
require_once(LOGIC_PATH . '/curl_get_contents.php');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
$botUser->accessCheck('pokedex');
include(LOGIC_PATH . '/resolve_boss_name_to_ids.php');

// Get raid levels
$id = $data['rl'] ?? 0;
$action = $data['a'] ?? '';

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
    'callback_data' => formatCallbackData(['pokebattler', 'rl' => implode(",", $pokebattler_levels)])
  );

  // Add key for each raid level
  foreach($pokebattler_levels as $l) {
    $keys[][] = array(
      'text'          => getTranslation($l . 'stars'),
      'callback_data' => formatCallbackData(['pokebattler', 'rl' => $l])
    );
  }
  $keys[][] = array(
    'text'          => getTranslation('1stars') . ' & ' . getTranslation('3stars'),
    'callback_data' => formatCallbackData(['pokebattler', 'rl' => '1,3'])
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
$msg = '<b>' . getTranslation('import') . SP . '(Pokebattler)' . '</b>' . CR . CR;
$ex_msg = '';

// Get pokebattler data.
debug_log('Getting raid bosses from pokebattler.com now...');
$link = 'https://fight.pokebattler.com/raids';
$pb_data = curl_get_contents($link);
$pb_data = json_decode($pb_data,true);

$get_levels = explode(',', $id);
$clear = $id;

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

// Init empty keys array.
$keys = [];

// Raid tier array
debug_log('Processing the following raid levels:');
$raidlevels = array();
foreach($get_levels as $level) {
  $raidlevels[] = 'RAID_LEVEL_' . $pokebattler_level_map[$level];
}
debug_log($raidlevels);
$levels_processed = $bosses = [];
// Process breaking news section
$now = new DateTime('now', new DateTimeZone($config->TIMEZONE));
$ph = new dateTimeZone('America/Los_Angeles');
foreach($pb_data['breakingNews'] as $news) {
  if($news['type'] != 'RAID_TYPE_RAID') continue;

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
      debug_log('Skipping raid boss ' . $raid['pokemon'] . ' since it has no id, it\'s likely in the future!');
      continue;
    }
    $dex_id_form = resolve_boss_name_to_ids($raid['pokemon']);
    $bosses[$raid_level_id][] = ['id' => $dex_id_form, 'shiny' => $raid['shiny']];
  }
}

foreach($get_levels as $raid_level_id) {
  if(!isset($bosses[$raid_level_id])) continue;
  if($raid_level_id > 5) $raid_level_text = getTranslation($raid_level_id . 'stars_short'); else $raid_level_text = $raid_level_id;
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

    // Save to database?
    if($action == 's') {
      // Shiny?
      $shiny = ($dex_id_form['shiny'] == 'true') ? 1 : 0;
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
    }

    // Exclude pokemon?
    if(in_array($pokemon_arg, $exclusions)) {
      // Add pokemon to exclude message.
      $ex_msg .= $local_pokemon . SP . '(#' . $dex_id . ')' . CR;

    } else {
      // Add pokemon to message.
      $msg .= $local_pokemon . SP . '(#' . $dex_id . ')' . CR;

      // Add key to exclude pokemon from import.
      $button_text_prefix = '';
      if($id == implode(",", $pokebattler_levels)) {
        // Add raid level to pokemon name
        $button_text_prefix = '[' . ($raid_level_text) . ']';
      }
      $e = $exclusions;
      $e[] = $pokemon_arg;
      // Are 3 raid bosses already selected?
      if(count($exclusions) == 3) continue;
      $keyAction = ($action == 's') ?
        ['pokedex_edit_pokemon', 'id' => $dex_id . "-" . $dex_form, 'arg' => ''] :
        ['pokebattler', 'rl' => $id, 'e' => implode('#', $e)];
      $keys[] = array(
        'text'          => $button_text_prefix . SP . $local_pokemon,
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
    'callback_data' => 'pokebattler'
  );

  // Save button.
  $nav_keys[] = array(
    'text'          => EMOJI_DISK,
    'callback_data' => formatCallbackData(['pokebattler', 'rl' => $id, 'a' => 's', 'e' => implode('#', $exclusions)])
  );

  // Reset button.
  if(isset($exclusions[0])) {
    $nav_keys[] = array(
      'text'          => getTranslation('reset'),
      'callback_data' => formatCallbackData(['pokebattler', 'rl' => $id])
    );
  }

  // Abort button.
  $nav_keys[] = array(
    'text'          => getTranslation('abort'),
    'callback_data' => 'exit'
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
