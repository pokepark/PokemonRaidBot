<?php
// Init SQL stuff.
$SQL = '';
$SQL_UPDATE = '';
$SQL_eggs = '';
$SQL_file = __DIR__ . '/sql/game-master-raid-boss-pokedex.sql';

$proto_url = "https://raw.githubusercontent.com/Furtif/POGOProtos/master/src/POGOProtos/Enums/Form.proto";
$game_master_url = "https://raw.githubusercontent.com/PokeMiners/game_masters/master/latest/latest.json";

//Parse the form ID's from pogoprotos
$proto = file($proto_url);
$count = count($proto);
$form_ids = array();
for($i=4;$i<$count;$i++) {
    $data = explode("=",str_replace(";","",$proto[$i]));
    if(count($data) == 2) $form_ids[trim($data[0])] = trim($data[1]);
}

$weatherboost_table = array(
                        "POKEMON_TYPE_BUG"      => "3",
                        "POKEMON_TYPE_DARK"     => "8",
                        "POKEMON_TYPE_DRAGON"   => "6",
                        "POKEMON_TYPE_ELECTRIC" => "3",
                        "POKEMON_TYPE_FAIRY"    => "5",
                        "POKEMON_TYPE_FIGHTING" => "5",
                        "POKEMON_TYPE_FIRE"     => "12",
                        "POKEMON_TYPE_FLYING"   => "6",
                        "POKEMON_TYPE_GHOST"    => "8",
                        "POKEMON_TYPE_GRASS"    => "12",
                        "POKEMON_TYPE_GROUND"   => "12",
                        "POKEMON_TYPE_ICE"      => "7",
                        "POKEMON_TYPE_NORMAL"   => "4",
                        "POKEMON_TYPE_POISON"   => "5",
                        "POKEMON_TYPE_PSYCHIC"  => "6",
                        "POKEMON_TYPE_ROCK"     => "4",
                        "POKEMON_TYPE_STEEL"    => "7",
                        "POKEMON_TYPE_WATER"    => "3"
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
    $part = explode("_",$row['templateId']);
    $form_data = [];
    $pokemon_id = "";
    if(count($part)<2) continue;
    if($part[0] == "FORMS") {
        // Found Pokemon form data

        // Get pokemon ID
        $pokemon_id = ltrim(str_replace("V","",$part[1]),'0');

        // Pokemon name 
        $pokemon_name = $row['data']['formSettings']['pokemon'];
        // Get pokemon forms
        if(!isset($row['data']['formSettings']['forms'])) {
            $form_data[] = array("form"=>$pokemon_name."_NORMAL");
        }else {
            $form_data = $row['data']['formSettings']['forms'];
        }
        foreach($form_data as $form) {
            $form_name = strtolower(str_replace($pokemon_name."_","",$form['form']));
            if($form_name != "purified" && $form_name != "shadow") {

                // Nidoran
                $poke_name = ucfirst(strtolower(str_replace(["_FEMALE","_MALE"],["♀","♂"],$pokemon_name)));
                // Ho-oh
                $poke_name = str_replace("_","-",$poke_name);

                $poke_shiny = 0;

                $form_id = $form_ids[$form['form']];
                $form_asset_suffix = (isset($form['assetBundleValue']) ? $form['assetBundleValue'] : (isset($form['assetBundleSuffix'])?$form['assetBundleSuffix']:"00"));

                $pokemon_array[$pokemon_id][$form_name] = [ "pokemon_name"=>$poke_name,
                                                            "pokemon_form_name"=>$form_name,
                                                            "pokemon_form_id"=>$form_id,
                                                            "asset_suffix"=>$form_asset_suffix,
                                                            "shiny"=>$poke_shiny
                                                          ];
            }
        }
    }else if ($part[1] == "POKEMON" && $part[0][0] == "V") {
        // Found Pokemon data
        $pokemon_id = (int)str_replace("V","",$part[0]);
        $form_name = str_replace($row['data']['pokemonSettings']['pokemonId']."_","",substr($row['data']['templateId'],14));

        if($form_name != "PURIFIED" && $form_name != "SHADOW" && $form_name != "NORMAL") {
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

            $weather = $weatherboost_table[$row['data']['pokemonSettings']['type']];
            if(isset($row['data']['pokemonSettings']['type2'])) {
                $weather .= $weatherboost_table[$row['data']['pokemonSettings']['type2']];
            }
            if(isset($pokemon_array[$pokemon_id][$form_name])) {
                $pokemon_array[$pokemon_id][$form_name]["min_cp"] = $min_cp;
                $pokemon_array[$pokemon_id][$form_name]["max_cp"] = $max_cp;
                $pokemon_array[$pokemon_id][$form_name]["min_weather_cp"] = $min_weather_cp;
                $pokemon_array[$pokemon_id][$form_name]["max_weather_cp"] = $max_weather_cp;
                $pokemon_array[$pokemon_id][$form_name]["weather"] = $weather;
            }else {
                // Fill data for Pokemon that have form data but no stats for forms specifically
                foreach($pokemon_array[$pokemon_id] as $form=>$data) {
                    $pokemon_array[$pokemon_id][$form]["min_cp"] = $min_cp;
                    $pokemon_array[$pokemon_id][$form]["max_cp"] = $max_cp;
                    $pokemon_array[$pokemon_id][$form]["min_weather_cp"] = $min_weather_cp;
                    $pokemon_array[$pokemon_id][$form]["max_weather_cp"] = $max_weather_cp;
                    $pokemon_array[$pokemon_id][$form]["weather"] = $weather;
                }
            }
        }
   }
}
// Save data to file.
if(!empty($pokemon_array)) {
    // Add eggs to SQL data.
    echo 'Adding raids eggs to pokemons' . PHP_EOL;
    for($e = 1; $e <= 5; $e++) {
        $pokemon_id = '999'.$e;
        $form_name = 'normal';
        $pokemon_name = 'Level '. $e .' Egg';
        $pokemon_array[$pokemon_id][$form_name] = [ "pokemon_name"=>$pokemon_name,
                                                    "pokemon_form_name"=>$form_name,
                                                    "pokemon_form_id"=>0,
                                                    "asset_suffix"=>0,
                                                    "shiny"=>0,
                                                    "min_cp"=>0,
                                                    "max_cp"=>0,
                                                    "min_weather_cp"=>0,
                                                    "max_weather_cp"=>0,
                                                    "weather"=>0
                                                  ];
    }

    // Add delete command to SQL data.
    echo 'Adding delete sql command to the beginning' . PHP_EOL;
    $DEL = 'DELETE FROM `pokemon`;' . PHP_EOL;
    $DEL .= 'TRUNCATE `pokemon`;' . PHP_EOL;
    foreach($pokemon_array as $id => $forms) {
        $pokemon_id = $id;
        foreach($forms as $form=>$data) {
            $poke_form = $form;

            $poke_name = $data['pokemon_name'];
            $form_id = $data['pokemon_form_id'];
            $form_asset_suffix = $data['asset_suffix'];
            $poke_min_cp = $data['min_cp'];
            $poke_max_cp = $data['max_cp'];
            $poke_min_weather_cp = $data['min_weather_cp'];
            $poke_max_weather_cp = $data['max_weather_cp'];

            $poke_weather  = $data['weather'];

            $poke_shiny = $data['shiny'];

            if($pokemon_id == 150 && $data['pokemon_form_name']=="a") {
                // Because logic and consistency
                $poke_form = "armored";
            }else {
                $poke_form = strtolower($data['pokemon_form_name']);
            }
            $QM = "'";
            $SEP = ",";
            echo $poke_name." ".$poke_form.PHP_EOL;
            $SQL .= "INSERT INTO pokemon (pokedex_id, pokemon_name, pokemon_form_name, pokemon_form_id, asset_suffix, min_cp, max_cp, min_weather_cp, max_weather_cp, weather, shiny) ";
            $SQL.= "VALUES (". $QM . $pokemon_id . $QM . $SEP . $QM . $poke_name . $QM . $SEP . $QM . $poke_form . $QM . $SEP . $QM . $form_id . $QM . $SEP . $QM . $form_asset_suffix . $QM . $SEP . $QM . $poke_min_cp . $QM . $SEP . $QM . $poke_max_cp . $QM . $SEP . $QM . $poke_min_weather_cp . $QM . $SEP . $QM . $poke_max_weather_cp . $QM . $SEP . $QM . $poke_weather . $QM . $SEP . $QM . $poke_shiny . $QM .");".PHP_EOL;
        }
    }
    $SQL = $DEL . $SQL . $SQL_UPDATE;
    // Save data.
    //echo $SQL . PHP_EOL;
    echo 'Saving data to ' . $SQL_file . PHP_EOL;
    file_put_contents($SQL_file, $SQL);
} else {
    echo 'Failed to get pokemon data!' . PHP_EOL;
}

// File successfully created?
if(is_file($SQL_file)) {
    echo 'Finished!' . PHP_EOL;
} else {
    echo 'Failed to save file: ' . $SQL_file . PHP_EOL;
}

?> 