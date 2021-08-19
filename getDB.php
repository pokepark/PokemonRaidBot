<?php

// Init SQL stuff.
$SQL = '';
$SQL_file = __DIR__ . '/sql/game-master-raid-boss-pokedex.sql';
$SQL_file_update = __DIR__ . '/sql/update-pokemon-table.sql';

$proto_url = "https://raw.githubusercontent.com/Furtif/POGOProtos/master/base/vbase.proto";
$game_master_url = "https://raw.githubusercontent.com/PokeMiners/game_masters/master/latest/latest.json";

$update = false;
if(isset($argv[1]) && $argv[1] == 'update') {
    define('CONFIG_PATH', __DIR__ . '/config');
    require_once(__DIR__ . '/core/bot/config.php');
    require_once(__DIR__ . '/core/bot/db.php');
    $update = true;
    $q = $dbh->query('SELECT pokedex_id, pokemon_form_name, shiny FROM pokemon');
    $pokemon_shiny = [];
    while($res = $q->fetch()) {
        $pokemon_shiny[$res['pokedex_id']][$res['pokemon_form_name']] = $res['shiny'];
    }
}
function check_shiny($pokemon_id,$pokemon_form){
    global $update, $pokemon_shiny;
    $shiny = 0;
    if($update && isset($pokemon_shiny[$pokemon_id][$pokemon_form])) {
        $shiny = $pokemon_shiny[$pokemon_id][$pokemon_form];
    }
    return $shiny;
}

//Parse the form ID's from pogoprotos
$proto = file($proto_url) or exit("Failed to open proto file." . PHP_EOL);
$count = count($proto);
$form_ids = array();
$start=false;
for($i=0;$i<$count;$i++) {
    $data = explode('=',str_replace(';','',$proto[$i]));
    // Found first pokemon, start collecting data
    if(count($data) == 2 && trim($data[0]) == 'UNOWN_A') $start = true;

    if($start) {
        // End of pokemon data, no need to loop further
        if(trim($data[0]) == '}') {
            break;
        }else if(count($data) == 2) {
            $form_ids[trim($data[0])] = trim($data[1]);
        }
    }
}
unset($proto);

// Set ID's for mega evolutions
// Using negative to prevent mixup with actual form ID's
// Collected from pogoprotos (hoping they won't change, so hard coding them here)
$mega_ids = array('MEGA'=>-1,'MEGA_X'=>-2,'MEGA_Y'=>-3);


$weatherboost_table = array(
                        'POKEMON_TYPE_BUG'      => '3',
                        'POKEMON_TYPE_DARK'     => '8',
                        'POKEMON_TYPE_DRAGON'   => '6',
                        'POKEMON_TYPE_ELECTRIC' => '3',
                        'POKEMON_TYPE_FAIRY'    => '5',
                        'POKEMON_TYPE_FIGHTING' => '5',
                        'POKEMON_TYPE_FIRE'     => '12',
                        'POKEMON_TYPE_FLYING'   => '6',
                        'POKEMON_TYPE_GHOST'    => '8',
                        'POKEMON_TYPE_GRASS'    => '12',
                        'POKEMON_TYPE_GROUND'   => '12',
                        'POKEMON_TYPE_ICE'      => '7',
                        'POKEMON_TYPE_NORMAL'   => '4',
                        'POKEMON_TYPE_POISON'   => '5',
                        'POKEMON_TYPE_PSYCHIC'  => '6',
                        'POKEMON_TYPE_ROCK'     => '4',
                        'POKEMON_TYPE_STEEL'    => '7',
                        'POKEMON_TYPE_WATER'    => '3'
                        );
function calculate_cps($base_stats) {
    //     CP = (Attack * Defense^0.5 * Stamina^0.5 * CP_Multiplier^2) / 10
    $cp_multiplier = array(20 => 0.5974 ,25 =>0.667934 );
    $min = floor((($base_stats['baseAttack']+10)*(($base_stats['baseDefense']+10)**0.5)*(($base_stats['baseStamina']+10)**0.5)*$cp_multiplier[20]**2)/10);
    $max = floor((($base_stats['baseAttack']+15)*(($base_stats['baseDefense']+15)**0.5)*(($base_stats['baseStamina']+15)**0.5)*$cp_multiplier[20]**2)/10);
    $min_weather = floor((($base_stats['baseAttack']+10)*(($base_stats['baseDefense']+10)**0.5)*(($base_stats['baseStamina']+10)**0.5)*$cp_multiplier[25]**2)/10);
    $max_weather = floor((($base_stats['baseAttack']+15)*(($base_stats['baseDefense']+15)**0.5)*(($base_stats['baseStamina']+15)**0.5)*$cp_multiplier[25]**2)/10);
    return [$min,$max,$min_weather,$max_weather];
}

$master = json_decode(file_get_contents($game_master_url),true);
foreach($master as $row) {
    $part = explode('_',$row['templateId']);
    $form_data = [];
    $pokemon_id = '';
    if(count($part)<2) continue;
    if($part[0] == 'FORMS') {
        // Found Pokemon form data

        // Get pokemon ID
        $pokemon_id = ltrim(str_replace('V','',$part[1]),'0');
        unset($part[0]);
        unset($part[1]);
        unset($part[2]);

        // Pokemon name 
        $pokemon_name = implode('_',$part);
        // Get pokemon forms
        if(!isset($row['data']['formSettings']['forms'])) {
            $form_data[] = array('form'=>$pokemon_name.'_NORMAL');
        }else {
            $form_data = $row['data']['formSettings']['forms'];
        }
        foreach($form_data as $form) {
            $form_name = strtolower(str_replace($pokemon_name.'_','',$form['form']));
            if($form_name != 'purified' && $form_name != 'shadow') {

                // Nidoran
                $poke_name = ucfirst(strtolower(str_replace(['_FEMALE','_MALE'],['♀','♂'],$row['data']['formSettings']['pokemon'])));
                // Ho-oh
                $poke_name = str_replace('_','-',$poke_name);

                if(!isset($form_ids[$form['form']])) {
                    $form_id = 0;
                }else {
                    $form_id = $form_ids[$form['form']];
                }
                $form_asset_suffix = (isset($form['assetBundleValue']) ? $form['assetBundleValue'] : (isset($form['assetBundleSuffix'])?$form['assetBundleSuffix']:'00'));

                $pokemon_array[$pokemon_id][$form_name] = [ 'pokemon_name'=>$poke_name,
                                                            'pokemon_form_name'=>$form_name,
                                                            'pokemon_form_id'=>$form_id,
                                                            'asset_suffix'=>$form_asset_suffix
                                                          ];
                
            }
        }
    }else if($part[0] == 'TEMPORARY' && $part[1] == 'EVOLUTION') {
        // Found Mega pokemon data
        // Get pokemon ID
        $pokemon_id = ltrim(str_replace('V','',$part[2]),'0');
        unset($part[0]);
        unset($part[1]);
        unset($part[2]);
        unset($part[3]);

        // Pokemon name
        $pokemon_name = implode("_",$part);
        $form_data = $row['data']['temporaryEvolutionSettings']['temporaryEvolutions'];
        foreach($form_data as $form) {
            // Nidoran
            $poke_name = ucfirst(strtolower(str_replace(["_FEMALE","_MALE"],["♀","♂"],$pokemon_name)));
            // Ho-oh
            $poke_name = str_replace("_","-",$poke_name);

            $form_name = str_replace("TEMP_EVOLUTION_","",$form['temporaryEvolutionId']);
            $form_asset_suffix = $form['assetBundleValue'];
            $form_id = $mega_ids[$form_name];

            $pokemon_array[$pokemon_id][$form_name] = [ "pokemon_name"=>$poke_name,
                                                        "pokemon_form_name"=>$form_name,
                                                        "pokemon_form_id"=>$form_id,
                                                        "asset_suffix"=>$form_asset_suffix
                                                      ];
        }
    }else if ($part[1] == "POKEMON" && $part[0][0] == "V" && isset($row['data']['pokemonSettings'])) {
        // Found Pokemon data
        $pokemon_id = (int)str_replace("V","",$part[0]);
        $form_name = str_replace($row['data']['pokemonSettings']['pokemonId']."_","",substr($row['data']['templateId'],14));
        if($form_name != 'PURIFIED' && $form_name != 'SHADOW' && $form_name != 'NORMAL'
          && isset($pokemon_array[$pokemon_id])
          && isset($row['data']['pokemonSettings']['stats']['baseAttack'])
          && isset($row['data']['pokemonSettings']['stats']['baseDefense'])
          && isset($row['data']['pokemonSettings']['stats']['baseStamina'])) {
            if($form_name == $row['data']['pokemonSettings']['pokemonId']) {
                $form_name = "normal";
            }else {
                $form_name = strtolower($form_name);
            }
            $CPs = calculate_cps($row['data']['pokemonSettings']['stats']);
            $min_cp = $CPs[0];
            $max_cp = $CPs[1];
            $min_weather_cp = $CPs[2];
            $max_weather_cp = $CPs[3];

            $type = strtolower(str_replace('POKEMON_TYPE_','', $row['data']['pokemonSettings']['type']));
            $type2 = '';

            $weather = $weatherboost_table[$row['data']['pokemonSettings']['type']];
            if(isset($row['data']['pokemonSettings']['type2'])) {
                $type2 = strtolower(str_replace('POKEMON_TYPE_','', $row['data']['pokemonSettings']['type2']));

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
                $pokemon_array[$pokemon_id][$form_name]['shiny'] = check_shiny($pokemon_id,$form_name);
            }else {
                // Fill data for Pokemon that have form data but no stats for forms specifically
                foreach($pokemon_array[$pokemon_id] as $form=>$data) {
                    $pokemon_array[$pokemon_id][$form]['min_cp'] = $min_cp;
                    $pokemon_array[$pokemon_id][$form]['max_cp'] = $max_cp;
                    $pokemon_array[$pokemon_id][$form]['min_weather_cp'] = $min_weather_cp;
                    $pokemon_array[$pokemon_id][$form]['max_weather_cp'] = $max_weather_cp;
                    $pokemon_array[$pokemon_id][$form]['weather'] = $weather;
                    $pokemon_array[$pokemon_id][$form]['type'] = $type;
                    $pokemon_array[$pokemon_id][$form]['type2'] = $type2;
                    $pokemon_array[$pokemon_id][$form]['shiny'] = check_shiny($pokemon_id,$form_name);
                }
            }
            if(isset($row['data']['pokemonSettings']['evolutionBranch'])) {
                foreach($row['data']['pokemonSettings']['evolutionBranch'] as $temp_evolution) {
                    if(isset($temp_evolution['temporaryEvolution'])) {
                        $form_name = str_replace('TEMP_EVOLUTION_','',$temp_evolution['temporaryEvolution']);
                        $pokemon_array[$pokemon_id][$form_name]['min_cp'] = $min_cp;
                        $pokemon_array[$pokemon_id][$form_name]['max_cp'] = $max_cp;
                        $pokemon_array[$pokemon_id][$form_name]['min_weather_cp'] = $min_weather_cp;
                        $pokemon_array[$pokemon_id][$form_name]['max_weather_cp'] = $max_weather_cp;
                        $pokemon_array[$pokemon_id][$form_name]['weather'] = $weather;
                        $pokemon_array[$pokemon_id][$form_name]['type'] = $type;
                        $pokemon_array[$pokemon_id][$form_name]['type2'] = $type2;
                        $pokemon_array[$pokemon_id][$form_name]['shiny'] = check_shiny($pokemon_id,$form_name);
                    }
                }
            }
        }
    }
}
// Save data to file.
if(!empty($pokemon_array)) {
    if($update) {
        $PRE = 'REPLACE INTO `pokemon`' . PHP_EOL;
        $PRE .= '(pokedex_id, pokemon_name, pokemon_form_name, pokemon_form_id, asset_suffix, min_cp, max_cp, min_weather_cp, max_weather_cp, type, type2, weather, shiny) VALUES';
    }else {
        // Add eggs to SQL data.
        echo 'Adding raids eggs to pokemons' . PHP_EOL;
        for($e = 1; $e <= 6; $e++) {
            $pokemon_id = '999'.$e;
            $form_name = 'normal';
            $pokemon_name = 'Level '. $e .' Egg';
            $pokemon_array[$pokemon_id][$form_name] = [ 'pokemon_name'=>$pokemon_name,
                                                        'pokemon_form_name'=>$form_name,
                                                        'pokemon_form_id'=>0,
                                                        'asset_suffix'=>0,
                                                        'shiny'=>0,
                                                        'min_cp'=>0,
                                                        'max_cp'=>0,
                                                        'min_weather_cp'=>0,
                                                        'max_weather_cp'=>0,
                                                        'type' => '',
                                                        'type2' => '',
                                                        'shiny'=>0,
                                                        'weather'=>0
                                                      ];
        }
        // Add delete command to SQL data.
        echo 'Adding delete sql command to the beginning' . PHP_EOL;
        $PRE = 'DELETE FROM `pokemon`;' . PHP_EOL;
        $PRE .= 'TRUNCATE `pokemon`;' . PHP_EOL;
        $PRE .= 'INSERT INTO `pokemon`' . PHP_EOL;
        $PRE .= '(pokedex_id, pokemon_name, pokemon_form_name, pokemon_form_id, asset_suffix, min_cp, max_cp, min_weather_cp, max_weather_cp, type, type2, weather) VALUES';
    }
    $i = 0;
    foreach($pokemon_array as $id => $forms) {
        $pokemon_id = $id;
        foreach($forms as $form=>$data) {
            // Check that data is set, if not the mon is probably not in the game yet and there's no point in having them in a broken state
            if(isset($data['weather']) && isset($data['min_cp']) && isset($data['max_cp']) && isset($data['min_weather_cp']) && isset($data['max_weather_cp'])) {
                $poke_form = $form;

                $poke_name = $data['pokemon_name'];
                $form_id = $data['pokemon_form_id'];
                $form_asset_suffix = $data['asset_suffix'];
                $poke_min_cp = $data['min_cp'];
                $poke_max_cp = $data['max_cp'];
                $poke_min_weather_cp = $data['min_weather_cp'];
                $poke_max_weather_cp = $data['max_weather_cp'];
                $poke_type = $data['type'];
                $poke_type2 = $data['type2'];
                $poke_weather  = $data['weather'];

                $poke_shiny = $data['shiny'];

                if($pokemon_id == 150 && $data['pokemon_form_name']=="a") {
                    // Because logic and consistency
                    $poke_form = 'armored';
                }else {
                    $poke_form = strtolower($data['pokemon_form_name']);
                }
                if($i==0) $i=1; else $SQL .= ",";
                $SQL .= PHP_EOL . "(\"${pokemon_id}\", \"${poke_name}\", \"${poke_form}\", \"${form_id}\", \"${form_asset_suffix}\", \"${poke_min_cp}\", \"${poke_max_cp}\", \"${poke_min_weather_cp}\", \"${poke_max_weather_cp}\", \"${poke_type}\", \"${poke_type2}\", \"${poke_weather}\"" . (($update) ? ", \"${poke_shiny}\"" : "") . ")";
            }
        }
    }
    $SQL = $PRE . $SQL .';';
    if($update) $save_file = $SQL_file_update;
    else $save_file = $SQL_file;
    echo 'Saving data to ' . $save_file . PHP_EOL;
    file_put_contents($save_file, $SQL);
} else {
    echo 'Failed to get pokemon data!' . PHP_EOL;
}

// File successfully created?
if(is_file($save_file)) {
    echo 'Finished!' . PHP_EOL;
} else {
    echo 'Failed to save file: ' . $save_file . PHP_EOL;
}

?> 
