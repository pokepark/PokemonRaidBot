<?php

// Exit if auto update is disabled from config
if(!$config->ENABLE_BOSS_AUTO_UPDATE) { exit; }

$levels = $data['id'];
$source = $data ['arg'];
$add_mons = [];
if($levels != 'scheduled') {
    $get_levels = explode(",",$levels);

    // Clear currently saved bosses that were imported with this method or inserted by hand
    disable_raid_level($levels);
    if($source == 'pogoinfo') {
        debug_log('Getting raid bosses from pogoinfo repository now...');
        $link = 'https://raw.githubusercontent.com/ccev/pogoinfo/v2/active/raids.json';
        $data = curl_get_contents($link);
        $data = json_decode($data,true);

        debug_log('Processing received ccev pogoinfo raid bosses for each raid level');
        foreach($data as $tier => $tier_pokemon) {
            // Process raid level?
            if(!in_array($tier,$get_levels)) {
                continue;
            }
            foreach($tier_pokemon as $raid_id_form) {
                $dex_id = $raid_id_form['id'];
                $dex_form = 0;
                if(isset($raid_id_form['temp_evolution_id'])) {
                    $dex_form = '-'.$raid_id_form['temp_evolution_id'];
                }elseif(isset($raid_id_form['form'])) {
                    $dex_form = $raid_id_form['form'];
                }else {
                    $query_form_id = my_query("SELECT pokemon_form_id FROM pokemon WHERE pokedex_id='".$dex_id."' and pokemon_form_name='normal' LIMIT 1")->fetch();
                    $dex_form = $query_form_id['pokemon_form_id'];
                }

                $add_mons[] = [ 
                                'pokedex_id' => $dex_id,
                                'pokemon_form_id' => $dex_form,
                                'raid_level' => $tier,
                            ];
            }
        }
    }else {
        info_log("Invalid argumens supplied to update_bosses!");
        exit();
    }
}elseif($levels == 'scheduled') {
    require_once(LOGIC_PATH . '/read_upcoming_bosses.php');
    $sql = 'DELETE FROM raid_bosses WHERE scheduled = 1;';
    $sql .= read_upcoming_bosses(true);
}else {
    info_log("Invalid argumens supplied to update_bosses!");
    exit();
}
$count = count($add_mons);
$start = false;
$sql_values = '';
if($count > 0) {
    $sql_cols = implode(", ", array_keys($add_mons[0]));
    for($i=0;$i<$count;$i++) {
        if($i > 0) $sql_values .= ',';
        $sql_values .= "('" . implode("', '", array_values($add_mons[$i])) . "')";
    }
    $sql = "INSERT INTO raid_bosses (" . $sql_cols . ") VALUES " . $sql_values . ";";
}

try {
    $query = $dbh->prepare($sql);
    $query->execute();
}catch (PDOException $exception) {
    info_log($exception->getMessage());
}

?>
