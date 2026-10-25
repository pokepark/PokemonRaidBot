<?php
require_once(LOGIC_PATH . '/curl_get_contents.php');

// Exit if auto update is disabled from config
if(!$config->ENABLE_BOSS_AUTO_UPDATE) { exit; }

$levels = $data['id'];
$source = $data['arg'];
if($levels != 'scheduled') {
  $get_levels = explode(',',$levels);

  require_once(LOGIC_PATH . '/disable_raid_level.php');
  // Clear currently saved bosses that were imported with this method or inserted by hand
  disable_raid_level($levels);
  if($source != 'pogoinfo') {
    info_log("Invalid argumens supplied to update_bosses!");
    exit();
  }
  debug_log('Getting raid bosses from pogoinfo repository now...');
  $link = 'https://raw.githubusercontent.com/ccev/pogoinfo/v2/active/raids.json';
  $data = curl_get_contents($link);
  $data = json_decode($data,true);

  debug_log('Processing received ccev pogoinfo raid bosses for each raid level');
  $sql_values = '';
  foreach($get_levels as $level) {
    if(!isset($data[$level])) continue;
    // Process requested levels
    foreach($data[$level] as $raid_id_form) {
      if(!isset($raid_id_form['id'])) continue;
      $dex_id = $raid_id_form['id'];
      $dex_form = 0;
      if(isset($raid_id_form['temp_evolution_id'])) {
        $dex_form = '-'.$raid_id_form['temp_evolution_id'];
      }elseif(isset($raid_id_form['form'])) {
        $dex_form = $raid_id_form['form'];
      }else {
        // If no form id is provided, let's check our db for normal form
        $query_form_id = my_query('SELECT pokemon_form_id FROM pokemon WHERE pokedex_id = ? and pokemon_form_name=\'normal\' LIMIT 1', [$dex_id]);
        if($query_form_id->rowCount() == 0) {
          // If normal form doesn't exist in our db, use the smallest form id as a fallback
          $query_form_id = my_query('SELECT min(pokemon_form_id) as pokemon_form_id FROM pokemon WHERE pokedex_id = ? LIMIT 1', [$dex_id]);
        }
        $result = $query_form_id->fetch();
        $dex_form = $result['pokemon_form_id'];
      }

      $sql_values .= '(\'' . implode("', '", [$dex_id, $dex_form, $level]) . '\'),';
    }
  }
  if($sql_values == '') exit;
  $sql_values = rtrim($sql_values, ',');

  $sql = 'INSERT INTO raid_bosses (pokedex_id, pokemon_form_id, raid_level) VALUES ' . $sql_values . ';';
}elseif($levels == 'scheduled') {
  require_once(LOGIC_PATH . '/read_upcoming_bosses.php');
  $upcoming = read_upcoming_bosses('array', [5,6,7,8,10]);
  if(empty($upcoming)) exit;
  $idArray = [];
  $updateRows = '';
  $updateCount = 0;
  // Exclude existing entries from deletion
  foreach($upcoming as $row) {
    $data = my_query('
      SELECT
        id, pokedex_id, pokemon_form_id, date_start, date_end, raid_level
      FROM raid_bosses
      WHERE pokedex_id = :pokedex_id
      AND pokemon_form_id = :pokemon_form_id
      AND date_start = :date_start
      AND date_end = :date_end
      AND scheduled = 1'
    ,['pokedex_id' => $row['pokedex_id'], 'pokemon_form_id' => $row['pokemon_form_id'], 'date_start'=>$row['date_start'], 'date_end'=>$row['date_end']]);
    $result = $data->fetchAll(PDO::FETCH_COLUMN, 0);
    if($data->rowCount() == 0) {
      $updateRows .= '(\'' . implode("', '", $row) . '\', \'1\'),';
      $updateCount++;
    }else {
      $idArray[] = $result[0];
    }
  }
  $updateRows = rtrim($updateRows, ',');

  $sql = 'DELETE FROM raid_bosses WHERE scheduled = 1 AND id NOT IN ('.implode(',', $idArray).'); ';
  if($updateCount > 0) $sql .= 'INSERT INTO raid_bosses (pokedex_id, pokemon_form_id, date_start, date_end, raid_level, scheduled) VALUES ' . $updateRows.';';
}else {
  info_log("Invalid argumens supplied to update_bosses!");
  exit();
}

my_query($sql);
