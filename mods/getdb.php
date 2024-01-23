<?php
require_once(__DIR__ . '/../core/bot/paths.php');
require_once(ROOT_PATH . '/constants.php');
require_once(CORE_BOT_PATH . '/config.php');
require_once(CORE_BOT_PATH . '/db.php');
require_once(LOGIC_PATH . '/sql_utils.php');
require_once(LOGIC_PATH . '/debug.php');
require_once(LOGIC_PATH . '/curl_get_contents.php');
$game_master_url = 'https://raw.githubusercontent.com/WatWowMap/Masterfile-Generator/master/master-latest-everything.json';

$error = false;

# We only have an update if the call came from TG
if(!isset($update)){
  $update = false;
}

// Parse the game master data together with form ids into format we can use
[$pokemon_array, $costume_data] = parse_master_data($game_master_url);
if(!$pokemon_array) {
  sendResults('Failed to open game master file.', $update, true);
}

// Save our core datasets to json files for further use
if(!file_put_contents(ROOT_PATH.'/protos/costume.json', json_encode($costume_data, JSON_PRETTY_PRINT))) {
  sendResults('Failed to write costume data to protos/costume.json', $update, true);
}

// Craft egg data
$PRE = 'INSERT INTO `pokemon`' . PHP_EOL;
$PRE .= '(pokedex_id, pokemon_name, pokemon_form_name, pokemon_form_id, min_cp, max_cp, min_weather_cp, max_weather_cp, type, type2, weather) VALUES';
foreach(EGGS as $egg) {
  $pokemon_id = $egg;
  $pokemon_name = 'Level '. str_replace('999', '', $egg) .' Egg';
  $pokemon_array[$pokemon_id]['normal'] = [
    'pokemon_name' => $pokemon_name,
    'pokemon_form_name' => 'normal',
    'pokemon_form_id' => 0,
    'shiny' => 0,
    'min_cp' => 0,
    'max_cp' => 0,
    'min_weather_cp' => 0,
    'max_weather_cp' => 0,
    'type' => '',
    'type2' => '',
    'weather' => 0,
  ];
}

// Craft the rest of the pokemon data
$i = 0;
$dataSql = '';
foreach($pokemon_array as $pokemon_id => $forms) {
  foreach($forms as $form => $data) {
    // Check that data is set, if not the mon is probably not in the game yet and there's no point in having them in a broken state
    if(!isset($data['weather']) || !isset($data['min_cp']) || !isset($data['max_cp']) || !isset($data['min_weather_cp']) || !isset($data['max_weather_cp']) || !isset($data['pokemon_name'])) continue;
    $insertData = [$pokemon_id, $data['pokemon_name'], $data['pokemon_form_name'], $data['pokemon_form_id'], $data['min_cp'], $data['max_cp'], $data['min_weather_cp'], $data['max_weather_cp'], $data['type'], $data['type2'], $data['weather']];
    $dataSql .= PHP_EOL . '("' . implode('","', $insertData) . '"),';
  }
}
## MySQL 8 compatible
#$SQL = $PRE . $SQL . ' as new' . PHP_EOL;
#$SQL .= 'ON DUPLICATE KEY UPDATE pokedex_id = new.pokedex_id, pokemon_name = new.pokemon_name, pokemon_form_name = new.pokemon_form_name,' . PHP_EOL;
#$SQL .= 'pokemon_form_id = new.pokemon_form_id, min_cp = new.min_cp, max_cp = new.max_cp,' . PHP_EOL;
#$SQL .= 'min_weather_cp = new.min_weather_cp, max_weather_cp = new.max_weather_cp, type = new.type, type2 = new.type2, weather = new.weather;';
$SQL = $PRE . rtrim($dataSql, ',') . PHP_EOL;
$SQL .= 'ON DUPLICATE KEY UPDATE pokedex_id = VALUES(pokedex_id), pokemon_name = VALUES(pokemon_name), pokemon_form_name = VALUES(pokemon_form_name),' . PHP_EOL;
$SQL .= 'pokemon_form_id = VALUES(pokemon_form_id), min_cp = VALUES(min_cp),' . PHP_EOL;
$SQL .= 'max_cp = VALUES(max_cp), min_weather_cp = VALUES(min_weather_cp), max_weather_cp = VALUES(max_weather_cp),' . PHP_EOL;
$SQL .= 'type = VALUES(type), type2 = VALUES(type2), weather = VALUES(weather);' . PHP_EOL;
try {
  $prep = $dbh->prepare($SQL);
  $prep->execute();
} catch (Exception $e) {
  sendResults($e, $update, true); 
}
$msg = 'Updated successfully!' . CR;
$msg.= $prep->rowCount() . ' rows required updating!';
// Sometimes Nia can push form id's a bit later than other stats, so the script may insert incomplete rows
// This hopefully clears those faulty rows out when the complete data is received without effecting any actual data
my_query('
  DELETE t1 FROM pokemon t1
  INNER JOIN pokemon t2
  WHERE
  t1.pokedex_id = t2.pokedex_id
  AND t1.pokemon_form_name = t2.pokemon_form_name
  AND t1.pokemon_form_name <> \'normal\'
  AND t1.pokemon_form_id = 0
');
sendResults($msg, $update);

function sendResults($msg, $update, $error = false) {
  if($error) {
    info_log('Pokemon table update failed: ' . $msg);
  }else if(!isset($update['callback_query']['id'])) {
    info_log($msg);
    exit();
  }
  $tg_json[] = answerCallbackQuery($update['callback_query']['id'], (!$error) ? 'OK!' : 'Error!', true);
  $tg_json[] = editMessageText($update['callback_query']['message']['message_id'], $msg, [], $update['callback_query']['message']['chat']['id'], false, true);
  curl_json_multi_request($tg_json);
  exit;
}
function calculate_cps($base_stats) {
  //   CP = (Attack * Defense^0.5 * Stamina^0.5 * CP_Multiplier^2) / 10
  $cp_multiplier = array(20 => 0.5974, 25 => 0.667934);
  $min = floor((($base_stats['attack']+10)*(($base_stats['defense']+10)**0.5)*(($base_stats['stamina']+10)**0.5)*$cp_multiplier[20]**2)/10);
  $max = floor((($base_stats['attack']+15)*(($base_stats['defense']+15)**0.5)*(($base_stats['stamina']+15)**0.5)*$cp_multiplier[20]**2)/10);
  $min_weather = floor((($base_stats['attack']+10)*(($base_stats['defense']+10)**0.5)*(($base_stats['stamina']+10)**0.5)*$cp_multiplier[25]**2)/10);
  $max_weather = floor((($base_stats['attack']+15)*(($base_stats['defense']+15)**0.5)*(($base_stats['stamina']+15)**0.5)*$cp_multiplier[25]**2)/10);
  return [$min,$max,$min_weather,$max_weather];
}

function parse_master_data($game_master_url) {
  // Set ID's for mega evolutions
  // Using negative to prevent mixup with actual form ID's
  // Collected from pogoprotos (hoping they won't change, so hard coding them here)
  $mega_names = array(-1 => 'mega', -2 => 'mega_x', -3 => 'mega_y', -4 => 'primal');
  $pokemon_array = [];
  $typeArray = [
    1 => 'normal',
    2 => 'fighting',
    3 => 'flying',
    4 => 'poison',
    5 => 'ground',
    6 => 'rock',
    7 => 'bug',
    8 => 'ghost',
    9 => 'steel',
    10 => 'fire',
    11 => 'water',
    12 => 'grass',
    13 => 'electric',
    14 => 'psychic',
    15 => 'ice',
    16 => 'dragon',
    17 => 'dark',
    18 => 'fairy',
  ];
  $weatherboost_table = array(
    1 => '4',
    2 => '5',
    3 => '6',
    4 => '5',
    5 => '12',
    6 => '4',
    7 => '3',
    8 => '8',
    9 => '7',
    10 => '12',
    11 => '3',
    12 => '12',
    13 => '3',
    14 => '6',
    15 => '7',
    16 => '6',
    17 => '8',
    18 => '5',
  );
  if(!$master_file = curl_get_contents($game_master_url)) return false;
  $master = json_decode($master_file, true);
  foreach($master['pokemon'] as $row) {
    $pokemon_id = $row['pokedexId'];
    $pokemon_name = $row['name'];
    if(!isset($row['stats']['attack']) || !isset($row['stats']['defense']) || !isset($row['stats']['stamina'])) {
      continue;
    }
    $type = $type2 = '';
    foreach($row['types'] as $key => $data) {
      if($type == '') {
        $type = $typeArray[$key];
        $weather = $weatherboost_table[$key];
        continue;
      }
      $type2 = $typeArray[$key];
      $weather .= $weatherboost_table[$key];
    }
    foreach($row['forms'] as $formData) {
      if(($formData['name'] == 'Unset' && count($row['forms']) > 1) || $formData['name'] == 'Shadow' || $formData['name'] == 'Purified') continue;
      if($formData['name'] == 'Normal') {
        $pokemon_array[$pokemon_id]['protoName'] = str_replace('_NORMAL', '', $formData['proto']);
        $form_name = 'normal';
      }else {
        if(isset($pokemon_array[$pokemon_id]['protoName']))
          $form_name = str_replace($pokemon_array[$pokemon_id]['protoName'].'_', '', $formData['proto']);
        else
          $form_name = ($formData['proto'] == 'FORM_UNSET') ? 'normal' : $formData['proto'];
      }
      [$min_cp, $max_cp, $min_weather_cp, $max_weather_cp] = (isset($formData['stats'])) ? calculate_cps($formData['stats']) : calculate_cps($row['stats']);
      $form_id = $formData['form'];
      $pokemon_array[$pokemon_id][$form_name] = [
        'pokemon_name'      => $pokemon_name,
        'pokemon_form_name' => $form_name,
        'pokemon_form_id'   => $form_id,
        'min_cp'            => $min_cp,
        'max_cp'            => $max_cp,
        'min_weather_cp'    => $min_weather_cp,
        'max_weather_cp'    => $max_weather_cp,
        'weather'           => $weather,
        'type'              => $type,
        'type2'             => $type2,
      ];
    }
    if(!isset($row['tempEvolutions'])) continue;
    foreach($row['tempEvolutions'] as $tempData) {
      if(isset($tempData['unreleased']) && $tempData['unreleased']) continue;
      $form_id = -$tempData['tempEvoId'];
      $form_name = $mega_names[$form_id];
      if(isset($tempData['types'])) {
        $type = '';
        foreach($tempData['types'] as $key => $data) {
          if($type == '') {
            $type = $typeArray[$key];
            $weather = $weatherboost_table[$key];
            continue;
          }
          $type2 = $typeArray[$key];
          $weather .= $weatherboost_table[$key];
        }
      }
      [$min_cp, $max_cp, $min_weather_cp, $max_weather_cp] = calculate_cps($row['stats']);
      $pokemon_array[$pokemon_id][$form_name] = [
        'pokemon_name'      => $pokemon_name,
        'pokemon_form_name' => $form_name,
        'pokemon_form_id'   => $form_id,
        'min_cp'            => $min_cp,
        'max_cp'            => $max_cp,
        'min_weather_cp'    => $min_weather_cp,
        'max_weather_cp'    => $max_weather_cp,
        'weather'           => $weather,
        'type'              => $type,
        'type2'             => $type2,
      ];
    }
  }
  $costume_data = [];
  foreach($master['costumes'] as $costume) {
    $costume_data[$costume['proto']] = $costume['id'];
  }
  return [$pokemon_array, $costume_data];
}
