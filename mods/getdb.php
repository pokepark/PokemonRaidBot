<?php
require_once(__DIR__ . '/../core/bot/paths.php');
require_once(ROOT_PATH . '/constants.php');
require_once(CORE_BOT_PATH . '/config.php');
require_once(CORE_BOT_PATH . '/db.php');
require_once(LOGIC_PATH . '/sql_utils.php');
require_once(LOGIC_PATH . '/debug.php');
require_once(LOGIC_PATH . '/curl_get_contents.php');
$proto_url = 'https://raw.githubusercontent.com/123FLO321/POGOProtos-Swift/master/Sources/POGOProtos/POGOProtos.pb.swift';
$game_master_url = 'https://raw.githubusercontent.com/PokeMiners/game_masters/master/latest/latest.json';

$error = false;

# We only have an update if the call came from TG
if(!isset($update)){
  $update = false;
}

// Read the form ids from protos
if (!$protos = get_protos($proto_url)) {
  sendResults('Failed to get protos.', $update, true);
}

[$form_ids, $costume] = $protos;
// Parse the game master data together with form ids into format we can use
$pokemon_array = parse_master_into_pokemon_table($form_ids, $game_master_url);
if(!$pokemon_array) {
  sendResults('Failed to open game master file.', $update, true);
}

// Save our core datasets to json files for further use
if(!file_put_contents(ROOT_PATH.'/protos/costume.json', json_encode($costume, JSON_PRETTY_PRINT))) {
  sendResults('Failed to write costume data to protos/costume.json', $update, true);
}
if(!file_put_contents(ROOT_PATH.'/protos/form.json', json_encode($form_ids, JSON_PRETTY_PRINT))) {
  sendResults('Failed to write form data to protos/form.json', $update, true);
}
if(!file_put_contents(ROOT_PATH.'/protos/pokemon.json', json_encode($pokemon_array, JSON_PRETTY_PRINT))) {
  sendResults('Failed to write pokemon data to protos/pokemon.json', $update, true);
}

// Craft egg data
$PRE = 'INSERT INTO `pokemon`' . PHP_EOL;
$PRE .= '(pokedex_id, pokemon_name, pokemon_form_name, pokemon_form_id, min_cp, max_cp, min_weather_cp, max_weather_cp, type, type2, weather) VALUES';
foreach($eggs as $egg) {
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
  if($update){
    $tg_json[] = answerCallbackQuery($update['callback_query']['id'], (!$error) ? 'OK!' : 'Error!', true);
    $tg_json[] = editMessageText($update['callback_query']['message']['message_id'], $msg, [], $update['callback_query']['message']['chat']['id'], false, true);
    curl_json_multi_request($tg_json);
  }
  exit;
}
function calculate_cps($base_stats) {
  //   CP = (Attack * Defense^0.5 * Stamina^0.5 * CP_Multiplier^2) / 10
  $cp_multiplier = array(20 => 0.5974, 25 => 0.667934);
  $min = floor((($base_stats['baseAttack']+10)*(($base_stats['baseDefense']+10)**0.5)*(($base_stats['baseStamina']+10)**0.5)*$cp_multiplier[20]**2)/10);
  $max = floor((($base_stats['baseAttack']+15)*(($base_stats['baseDefense']+15)**0.5)*(($base_stats['baseStamina']+15)**0.5)*$cp_multiplier[20]**2)/10);
  $min_weather = floor((($base_stats['baseAttack']+10)*(($base_stats['baseDefense']+10)**0.5)*(($base_stats['baseStamina']+10)**0.5)*$cp_multiplier[25]**2)/10);
  $max_weather = floor((($base_stats['baseAttack']+15)*(($base_stats['baseDefense']+15)**0.5)*(($base_stats['baseStamina']+15)**0.5)*$cp_multiplier[25]**2)/10);
  return [$min,$max,$min_weather,$max_weather];
}

function get_protos($proto_url) {
  //Parse the form ID's from pogoprotos
  if(!$proto_file = curl_get_contents($proto_url)) return false;
  $proto =  preg_split('/\r\n|\r|\n/', $proto_file);
  $count = count($proto);
  $form_ids = $costume = array();
  $data_array = false;
  for($i=0;$i<$count;$i++) {
    $line = trim($proto[$i]);
    if($data_array != false) {
      $data = explode(':', $line, 2);
      // End of pokemon data, no need to loop further
      if(trim($data[0]) == ']') {
        $data_array = false;
        if(count($form_ids) > 0 && count($costume) > 0) {
          // We found what we needed so we can stop looping through proto file and exit
          break;
        }
        continue;
      }
      $value = explode('"', $data[1]);
      if(strlen($value[1]) > 0) {
        ${$data_array}[trim($value[1])] = trim($data[0]);
      }
      continue;
    }
    if($line == 'extension PokemonDisplayProto.Costume: SwiftProtobuf._ProtoNameProviding {') {
      $data_array = 'costume';
      $i++; // Jump over one line
    }
    if($line == 'extension PokemonDisplayProto.Form: SwiftProtobuf._ProtoNameProviding {') {
      $data_array = 'form_ids';
      $i++; // Jump over one line
    }
  }
  unset($proto);
  return [$form_ids, $costume];
}

function parse_master_into_pokemon_table($form_ids, $game_master_url) {
  // Set ID's for mega evolutions
  // Using negative to prevent mixup with actual form ID's
  // Collected from pogoprotos (hoping they won't change, so hard coding them here)
  $mega_ids = array('MEGA' => -1, 'MEGA_X' => -2, 'MEGA_Y' => -3, 'PRIMAL' => -4);

  $weatherboost_table = array(
    'POKEMON_TYPE_BUG'    => '3',
    'POKEMON_TYPE_DARK'   => '8',
    'POKEMON_TYPE_DRAGON'   => '6',
    'POKEMON_TYPE_ELECTRIC' => '3',
    'POKEMON_TYPE_FAIRY'  => '5',
    'POKEMON_TYPE_FIGHTING' => '5',
    'POKEMON_TYPE_FIRE'   => '12',
    'POKEMON_TYPE_FLYING'   => '6',
    'POKEMON_TYPE_GHOST'  => '8',
    'POKEMON_TYPE_GRASS'  => '12',
    'POKEMON_TYPE_GROUND'   => '12',
    'POKEMON_TYPE_ICE'    => '7',
    'POKEMON_TYPE_NORMAL'   => '4',
    'POKEMON_TYPE_POISON'   => '5',
    'POKEMON_TYPE_PSYCHIC'  => '6',
    'POKEMON_TYPE_ROCK'   => '4',
    'POKEMON_TYPE_STEEL'  => '7',
    'POKEMON_TYPE_WATER'  => '3'
  );
  if(!$master_file = curl_get_contents($game_master_url)) return false;
  $master = json_decode($master_file, true);
  foreach($master as $row) {
    $part = explode('_', $row['templateId']);
    $form_data = [];
    $pokemon_id = '';
    if(count($part) < 2) continue;
    if(preg_match('/FORMS_V([0-9]*)_POKEMON_([a-zA-Z0-9_]*)/', $row['templateId'], $matches)) {
      // Found Pokemon form data
      $pokemon_id = (int)$matches[1];
      $pokemon_name = $matches[2];
      // Get pokemon forms
      if(!isset($row['data']['formSettings']['forms']) or empty($row['data']['formSettings']['forms'][0])) {
        $form_data[] = array('form' => $pokemon_name . '_NORMAL');
      }else {
        $form_data = $row['data']['formSettings']['forms'];
      }
      foreach($form_data as $form) {
        $form_name = strtolower(str_replace($pokemon_name.'_','',$form['form']));
        if($form_name == 'purified' || $form_name == 'shadow') continue;
        $poke_name = ucfirst(strtolower($row['data']['formSettings']['pokemon']));
        $form_id = $form_ids[$form['form']] ?? 0;

        $pokemon_array[$pokemon_id][$form_name] = [
          'pokemon_name' => $poke_name,
          'pokemon_form_name' => $form_name,
          'pokemon_form_id' => $form_id,
        ];
      }
    }else if (preg_match('/V([0-9]*)_POKEMON_([a-zA-Z0-9_]*)/', $row['templateId'], $matches) && isset($row['data']['pokemonSettings'])) {
      // Found Pokemon data
      $pokemon_id = (int)$matches[1];
      $form_name = str_replace($row['data']['pokemonSettings']['pokemonId'] . '_', '', $matches[2]);
      if($form_name == 'PURIFIED' || $form_name == 'SHADOW' || $form_name == 'NORMAL'
        || !isset($pokemon_array[$pokemon_id])
        || !isset($row['data']['pokemonSettings']['stats']['baseAttack'])
        || !isset($row['data']['pokemonSettings']['stats']['baseDefense'])
        || !isset($row['data']['pokemonSettings']['stats']['baseStamina'])
      ) {
        continue;
      }
      if($form_name != $row['data']['pokemonSettings']['pokemonId']) {
        $form_name = strtolower($form_name);
      }else {
        $form_name = 'normal';
      }
      [$min_cp, $max_cp, $min_weather_cp, $max_weather_cp] = calculate_cps($row['data']['pokemonSettings']['stats']);

      $type = strtolower(str_replace('POKEMON_TYPE_', '', $row['data']['pokemonSettings']['type']));
      $type2 = '';

      $weather = $weatherboost_table[$row['data']['pokemonSettings']['type']];
      if(isset($row['data']['pokemonSettings']['type2'])) {
        $type2 = strtolower(str_replace('POKEMON_TYPE_', '', $row['data']['pokemonSettings']['type2']));

        # Add type2 weather boost only if there is a second type and it's not the same weather as the first type!
        if($weatherboost_table[$row['data']['pokemonSettings']['type2']] != $weatherboost_table[$row['data']['pokemonSettings']['type']]) {
          $weather .= $weatherboost_table[$row['data']['pokemonSettings']['type2']];
        }
      }
      if(isset($pokemon_array[$pokemon_id][$form_name])) {
        $pokemon_array[$pokemon_id][$form_name]['min_cp'] = $min_cp;
        $pokemon_array[$pokemon_id][$form_name]['max_cp'] = $max_cp;
        $pokemon_array[$pokemon_id][$form_name]['min_weather_cp'] = $min_weather_cp;
        $pokemon_array[$pokemon_id][$form_name]['max_weather_cp'] = $max_weather_cp;
        $pokemon_array[$pokemon_id][$form_name]['weather'] = $weather;
        $pokemon_array[$pokemon_id][$form_name]['type'] = $type;
        $pokemon_array[$pokemon_id][$form_name]['type2'] = $type2;
      }else {
        // Fill data for Pokemon that have form data but no stats for forms specifically
        foreach($pokemon_array[$pokemon_id] as $form => $data) {
          $pokemon_array[$pokemon_id][$form]['min_cp'] = $min_cp;
          $pokemon_array[$pokemon_id][$form]['max_cp'] = $max_cp;
          $pokemon_array[$pokemon_id][$form]['min_weather_cp'] = $min_weather_cp;
          $pokemon_array[$pokemon_id][$form]['max_weather_cp'] = $max_weather_cp;
          $pokemon_array[$pokemon_id][$form]['weather'] = $weather;
          $pokemon_array[$pokemon_id][$form]['type'] = $type;
          $pokemon_array[$pokemon_id][$form]['type2'] = $type2;
        }
      }
      if(!isset($row['data']['pokemonSettings']['tempEvoOverrides'])) continue;
      foreach($row['data']['pokemonSettings']['tempEvoOverrides'] as $temp_evolution) {
        if(!isset($temp_evolution['tempEvoId'])) continue;

        $mega_evolution_name = str_replace('TEMP_EVOLUTION_', '', $temp_evolution['tempEvoId']);
        // We only override the types for megas
        // weather info is used to display boosts for caught mons, which often are different from mega's typing
        $typeOverride = strtolower(str_replace('POKEMON_TYPE_', '', $temp_evolution['typeOverride1']));
        $typeOverride2 = '';

        if(isset($temp_evolution['typeOverride2'])) {
          $typeOverride2 = strtolower(str_replace('POKEMON_TYPE_', '', $temp_evolution['typeOverride2']));
        }
        $pokemon_array[$pokemon_id][$mega_evolution_name] = [
          'pokemon_name'    => $pokemon_array[$pokemon_id][$form_name]['pokemon_name'],
          'pokemon_form_name' => strtolower($mega_evolution_name),
          'pokemon_form_id'   => $mega_ids[$mega_evolution_name],
          'min_cp'      => $min_cp,
          'max_cp'      => $max_cp,
          'min_weather_cp'  => $min_weather_cp,
          'max_weather_cp'  => $max_weather_cp,
          'weather'       => $weather,
          'type'        => $typeOverride,
          'type2'       => $typeOverride2,
        ];
      }
    }
  }
  return $pokemon_array;
}
