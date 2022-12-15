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

$link = 'https://fight.pokebattler.com/raids';
$pb_data = curl_get_contents($link);
$pb_data = json_decode($pb_data,true);

// Init empty keys array.
$keys = [];
$msg = '';
$shinydata = [];
foreach($pb_data['tiers'] as $tier) {
  // Raid level and message.
  $rl = str_replace('RAID_LEVEL_','', $tier['tier']);
  if($rl == "MEGA") $raid_level_id = 6; else $raid_level_id = $rl;
  $rl_parts = explode('_', $rl);
  if($rl_parts[count($rl_parts)-1] == 'FUTURE') continue;

  // Get raid bosses for each raid level.
  foreach($tier['raids'] as $raid) {
    if(!isset($raid['pokemon']) || $raid['shiny'] != 'true') continue;

    // Get ID and form name used internally.
    [$dex_id, $dex_form] = resolve_boss_name_to_ids($raid['pokemon']);

    // Make sure we received a valid dex id.
    if(!is_numeric($dex_id) || $dex_id == 0) {
      info_log('Failed to get a valid pokemon dex id: '. $dex_id .', pokemon: ' . $raid['pokemon'] . '. Continuing with next raid boss...', 'Import shinyinfo:');
      continue;
    }
    $shinydata[] = [':dex_id' => $dex_id, ':dex_form' => $dex_form];
  }
}
// Back button.
$keys[][] = button(getTranslation('done'), ['exit', 'd' => '1']);
if(count($shinydata) > 0) {
  $query = $dbh->prepare("UPDATE pokemon SET shiny = 1 WHERE pokedex_id = :dex_id AND pokemon_form_id = :dex_form");
  foreach($shinydata as $row_data) {
    $query->execute($row_data);
  }
}

$msg .= 'Updated '.count($shinydata).' rows'.CR;

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
