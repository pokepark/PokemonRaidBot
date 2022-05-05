<?php
$proto_url = getProtoURL();
$game_master_url = "https://raw.githubusercontent.com/PokeMiners/game_masters/master/latest/latest.json";

$error = false;

// Read the form ids from protos
if($protos = get_protos($proto_url)) {
    global $dbh;
    $form_ids = $protos[0];
    $costume = $protos[1];

    // Save costume data to json file
    if(file_put_contents(ROOT_PATH.'/protos/costume.json', json_encode($costume, JSON_PRETTY_PRINT))) {
        // Parse the game master data together with form ids into format we can use
        $pokemon_array = parse_master_into_pokemon_table($form_ids, $game_master_url);
        if(!$pokemon_array) {
            $error =  "Failed to open game master file.";
        } else {
            $PRE = 'INSERT INTO `pokemon`' . PHP_EOL;
            $PRE .= '(pokedex_id, pokemon_name, pokemon_form_name, pokemon_form_id, asset_suffix, min_cp, max_cp, min_weather_cp, max_weather_cp, type, type2, weather) VALUES';
            foreach($eggs as $egg) {
                $pokemon_id = $egg;
                $form_name = 'normal';
                $pokemon_name = 'Level '. $egg[3] .' Egg';
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
            $i = 0;
            $SQL = '';
            foreach($pokemon_array as $id => $forms) {
                $pokemon_id = $id;
                foreach($forms as $form=>$data) {
                    // Check that data is set, if not the mon is probably not in the game yet and there's no point in having them in a broken state
                    if(isset($data['weather']) && isset($data['min_cp']) && isset($data['max_cp']) && isset($data['min_weather_cp']) && isset($data['max_weather_cp']) && isset($data['pokemon_name'])) {
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
            
                        if($pokemon_id == 150 && $data['pokemon_form_name']=="a") {
                            // Because logic and consistency
                            $poke_form = 'armored';
                        }else {
                            $poke_form = strtolower($data['pokemon_form_name']);
                        }
                        if($i==0) $i=1; else $SQL .= ",";
                        $SQL .= PHP_EOL . "(\"${pokemon_id}\", \"${poke_name}\", \"${poke_form}\", \"${form_id}\", \"${form_asset_suffix}\", \"${poke_min_cp}\", \"${poke_max_cp}\", \"${poke_min_weather_cp}\", \"${poke_max_weather_cp}\", \"${poke_type}\", \"${poke_type2}\", \"${poke_weather}\")";
                    }
                }
            }
            ## MySQL 8 compatible
            #$SQL = $PRE . $SQL . ' as new' . PHP_EOL;
            #$SQL .= 'ON DUPLICATE KEY UPDATE pokedex_id = new.pokedex_id, pokemon_name = new.pokemon_name, pokemon_form_name = new.pokemon_form_name,' . PHP_EOL;
            #$SQL .= 'pokemon_form_id = new.pokemon_form_id, asset_suffix = new.asset_suffix, min_cp = new.min_cp, max_cp = new.max_cp,' . PHP_EOL;
            #$SQL .= 'min_weather_cp = new.min_weather_cp, max_weather_cp = new.max_weather_cp, type = new.type, type2 = new.type2, weather = new.weather;';
            $SQL = $PRE . $SQL . PHP_EOL;
            $SQL .= 'ON DUPLICATE KEY UPDATE pokedex_id = VALUES(pokedex_id), pokemon_name = VALUES(pokemon_name), pokemon_form_name = VALUES(pokemon_form_name),' . PHP_EOL;
            $SQL .= 'pokemon_form_id = VALUES(pokemon_form_id), asset_suffix = VALUES(asset_suffix), min_cp = VALUES(min_cp),' . PHP_EOL;
            $SQL .= 'max_cp = VALUES(max_cp), min_weather_cp = VALUES(min_weather_cp), max_weather_cp = VALUES(max_weather_cp),' . PHP_EOL;
            $SQL .= 'type = VALUES(type), type2 = VALUES(type2), weather = VALUES(weather);' . PHP_EOL;
            try {
                $prep = $dbh->prepare($SQL);
                $prep->execute();
            } catch (Exception $e) {
                if(isset($update['message']['from']['id'])) $error = $e;
            }
        }
    }else {
        $error = 'Failed to write costume data to protos/costume.json';
    }
} else {
    $error = 'Failed to get protos.';
}
if(!$error) { 
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
    $callback_msg = 'OK!';
}else {
    $msg = $error;
    info_log('Pokemon table update failed: ' . $error);
    $callback_msg = 'Error!';
}
if(isset($update['callback_query']['id'])) {
    // Answer callback.
    $tg_json[] = answerCallbackQuery($update['callback_query']['id'], $callback_msg, true);

    // Edit the message.
    $tg_json[] = editMessageText($update['callback_query']['message']['message_id'], $msg, [], $update['callback_query']['message']['chat']['id'], false, true);

    // Telegram multicurl request.
    curl_json_multi_request($tg_json);

    // Exit.
    $dbh = null;
    exit();
}

function calculate_cps($base_stats) {
    //     CP = (Attack * Defense^0.5 * Stamina^0.5 * CP_Multiplier^2) / 10
    $cp_multiplier = array(20 => 0.5974 ,25 =>0.667934 );
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
            $data = explode('=',str_replace(';','',$line));
            // End of pokemon data, no need to loop further
            if(trim($data[0]) == '}') {
                $data_array = false;
                if(count($form_ids) > 0 && count($costume) > 0) {
                    // We found what we needed so we can stop looping through proto file and exit
                    break;
                }
            }else if(count($data) == 2) {
                ${$data_array}[trim($data[0])] = trim($data[1]);
            }
        }else {
            if($line == 'enum Costume {') {
                $data_array = 'costume';
            }
            if($line == 'enum Form {') {
                $data_array = 'form_ids';
            }
        }
    }
    unset($proto);
    return [$form_ids, $costume];
}

function parse_master_into_pokemon_table($form_ids, $game_master_url) {
    // Set ID's for mega evolutions
    // Using negative to prevent mixup with actual form ID's
    // Collected from pogoprotos (hoping they won't change, so hard coding them here)
    $mega_ids = array('MEGA'=>-1,'MEGA_X'=>-2,'MEGA_Y'=>-3);
    $mega_asset_suffixes = array('MEGA'=>51,'MEGA_X'=>51,'MEGA_Y'=>52);

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
    if(!$master_file = curl_get_contents($game_master_url)) return false;
    $master = json_decode($master_file, true);
    foreach($master as $row) {
        $part = explode('_',$row['templateId']);
        $form_data = [];
        $pokemon_id = '';
        if(count($part)<2) continue;
        if($part[0] == 'FORMS' && $part[2] == 'POKEMON') {
            // Found Pokemon form data

            // Get pokemon ID
            $pokemon_id = ltrim(str_replace('V','',$part[1]),'0');
            unset($part[0]);
            unset($part[1]);
            unset($part[2]);

            // Pokemon name 
            $pokemon_name = implode('_',$part);
            // Get pokemon forms
            if(!isset($row['data']['formSettings']['forms']) or empty($row['data']['formSettings']['forms'][0])) {
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
                    }
                }
                if(isset($row['data']['pokemonSettings']['tempEvoOverrides'])) {
                    foreach($row['data']['pokemonSettings']['tempEvoOverrides'] as $temp_evolution) {
                        if(isset($temp_evolution['tempEvoId'])) {
                            $mega_evolution_name = str_replace('TEMP_EVOLUTION_','',$temp_evolution['tempEvoId']);
                            // We only override the types for megas
                            // weather info is used to display boosts for caught mons, which often are different from mega's typing
                            $typeOverride = strtolower(str_replace('POKEMON_TYPE_','', $temp_evolution['typeOverride1']));
                            $typeOverride2 = '';

                            if(isset($temp_evolution['typeOverride2'])) {
                                $typeOverride2 = strtolower(str_replace('POKEMON_TYPE_','', $temp_evolution['typeOverride2']));
                            }
                            $pokemon_array[$pokemon_id][$mega_evolution_name] = [   'pokemon_name'      => $pokemon_array[$pokemon_id][$form_name]['pokemon_name'],
                                                                                    'pokemon_form_name' => $mega_evolution_name,
                                                                                    'pokemon_form_id'   => $mega_ids[$mega_evolution_name],
                                                                                    'asset_suffix'      => $mega_asset_suffixes[$mega_evolution_name],
                                                                                    'min_cp'            => $min_cp,
                                                                                    'max_cp'            => $max_cp,
                                                                                    'min_weather_cp'    => $min_weather_cp,
                                                                                    'max_weather_cp'    => $max_weather_cp,
                                                                                    'weather'           => $weather,
                                                                                    'type'              => $typeOverride,
                                                                                    'type2'             => $typeOverride2,
                                                                                ];
                        }
                    }
                }
            }
        }
    }
    return $pokemon_array;
}

// Fetch the latest version of proto files.
// vbase.proto has only the latest fully deobfuscated protos,
// but we only need the latest form and costume data which is available in the partially obfuscated protofiles
function getProtoURL() {
    $repo_owner = 'Furtif';
    $repo_name = 'POGOProtos';
    $content_dir = 'base';

    $repo_content = 'https://api.github.com/repos/' . $repo_owner . '/' . $repo_name . '/contents/' . $content_dir;
    // Git tree lookup
    $tree = curl_get_contents($repo_content);
    $leaf = json_decode($tree, true);
    // Detect rate-limiting and die gracefully
    if(is_array($leaf) && in_array('message', $leaf)) {
        die('Failed to download repo index: ' . $leaf['message']);
    }
    $highest = 0;
    $url = '';
    foreach($leaf as $l) {
        $version = trim(preg_replace('/\D/', '', substr($l['name'], 3)));
        if($version > $highest) {
            $split = explode(".",$l['name']);
            // Only allow fully or partially deobfuscated iterations of the proto file
            if($split[2] == 'x' or $split[2] == 'x_p_obf') {
                $highest = $version;
                $url = $l['download_url'];
            }
        }
    }
    return $url;
}

?> 
