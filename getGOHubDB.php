<?php
// Init SQL stuff.
$SQL = '';
$SQL_eggs = '';
$SQL_file = __DIR__ . '/sql/gohub-raid-boss-pokedex.sql';

// Pokemon IDs.
$first_dex_id = 1;
$last_dex_id = 809;

// Get JSON
function getPokemonData($pokedex_id, $pokemon_form = 'Normal') {
    // Set DB URL.
    $DB_URL = 'https://db.pokemongohub.net/api/pokemon/';

    // Build URL for CURL.
    $URL = $DB_URL . $pokedex_id . (($pokemon_form != 'Normal') ? ('?form=' . $pokemon_form) : '');

    // Get data.
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $URL);
    $data = curl_exec($ch);
    curl_close($ch);

    return $data;
}

// Format Pokemon Data.
function formatPokemonData($pokemon, $DB_ID) {
    // Get data.
    $poke_id = $pokemon['id'];
    $poke_name = str_replace("'",'’',$pokemon['name']); // Farfetch’d
    $poke_form = $pokemon['form'] === NULL ? 'normal' : ((strtolower($pokemon['form']) == 'alola') ? (strtolower($pokemon['form']) . 'n') : strtolower($pokemon['form'])); // Always alolan with n at the end, not alola!
    $poke_raid_level = 0;
    $poke_min_cp = $pokemon['CPs']['raidCaptureMin'];
    $poke_max_cp = $pokemon['CPs']['raidCaptureMax'];
    $poke_min_weather_cp = $pokemon['CPs']['raidCaptureBoostMin'];
    $poke_max_weather_cp = $pokemon['CPs']['raidCaptureBoostMax'];
    $poke_weather = implode(',',$pokemon['weatherInfluences']);

    // Replace weather names with values.
    $poke_weather = str_replace('sunny',12,$poke_weather);
    $poke_weather = str_replace('rain',3,$poke_weather);
    $poke_weather = str_replace('partlyCloudy',4,$poke_weather);
    $poke_weather = str_replace('cloudy',5,$poke_weather);
    $poke_weather = str_replace('windy',6,$poke_weather);
    $poke_weather = str_replace('snow',7,$poke_weather);
    $poke_weather = str_replace('fog',8,$poke_weather);
    $poke_weather = str_replace(',','',$poke_weather);

    // Build SQL Command:
    $SEP = ',';
    $QM = "'";
    $SQL_pokemon = 'INSERT INTO `pokemon` VALUES (' . $DB_ID . $SEP . $poke_id . $SEP . $QM . $poke_name . $QM . $SEP . $QM . $poke_form . $QM . $SEP . $QM . $poke_raid_level . $QM . $SEP;
    $SQL_pokemon .= $poke_min_cp . $SEP . $poke_max_cp . $SEP . $poke_min_weather_cp . $SEP . $poke_max_weather_cp . $SEP . $poke_weather . ');' . PHP_EOL;

    // Return SQL;
    return $SQL_pokemon;
}

// Get data for each pokemon.
echo 'Starting!' . PHP_EOL;
echo 'Getting data for every pokemon id from ' . $first_dex_id . ' to ' . $last_dex_id . PHP_EOL;
for($i = $first_dex_id; $i <= $last_dex_id; $i++) {
    // Init/Reset stuff.
    $pokemon = 0;
    $form = 0;
    $json = 0;
    $DB_ID = (isset($DB_ID)) ? ($DB_ID + 1) : 1;

    // Get pokemon data.
    $json = getPokemonData($i);
    $pokemon = json_decode($json, true);

    // Create SQL command for pokemon.
    if(is_array($pokemon)) {
        // Make sure it's normal form (NULL)
        if($pokemon['form'] === NULL) {
            echo 'Formatting data for pokemon id ' . $i . PHP_EOL;
            $SQL .= formatPokemonData($pokemon, $DB_ID);
        // Skip if first form is not normal form, e.g. Giratina only having altered and origin as forms but no normal form
        } else {
            echo 'Missing normal form for pokemon id ' . $i . PHP_EOL;
            echo 'Skipping normal form for pokemon id ' . $i . PHP_EOL;
        }
    } else {
        echo 'No data received for pokemon id ' . $i . PHP_EOL;
        continue;
    }

    // Get number of forms.
    $forms_count = count($pokemon['forms']);

    // Multiple forms?
    if($forms_count > 1) {
        // Get data for each form.
        foreach($pokemon['forms'] as $key => $form) {
            // Get form name and value
            $form_name = $form['name'];
            $form_value = $form['value'];

            // Skip normal form
            if(strtolower($form_name) == 'normal' || $form_value === NULL) continue;

            // Get pokemon data.
            $json = getPokemonData($i, $form_value);
            $pokemon = json_decode($json, true);

            // Get pokemon data for form
            if(is_array($pokemon)) {
                echo 'Formatting data for pokemon id ' . $i . ' (Form: ' . $form_name . ')' . PHP_EOL;
                $DB_ID = $DB_ID + 1;
                $SQL .= formatPokemonData($pokemon, $DB_ID);
            } else {
                echo 'No data received for pokemon id ' . $i . ' (Form: ' . $form_name . ')' . PHP_EOL;
            }
        }
    }
}

// Save data to file.
if(!empty($SQL)) {
    // Add eggs to SQL data.
    echo 'Adding raids eggs to pokemons' . PHP_EOL;
    for($e = 1; $e <= 5; $e++) {
        $DB_ID = (isset($DB_ID)) ? ($DB_ID + 1) : 1;
        $SEP = ',';
        $QM = "'";
        $SQL .= 'INSERT INTO `pokemon` VALUES (' . $DB_ID . $SEP . '999' . $e . $SEP . $QM . 'Level ' . $e . ' Egg' . $QM . $SEP . $QM . 'normal' . $QM . $SEP . $QM . '0' . $QM . $SEP . '0,0,0,0,0);' . PHP_EOL;
    }

    // Add delete command to SQL data.
    echo 'Adding delete sql command to the beginning' . PHP_EOL;
    $DEL = 'DELETE FROM `pokemon`;' . PHP_EOL;
    $DEL .= 'TRUNCATE `pokemon`;' . PHP_EOL;
    $SQL = $DEL . $SQL;

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
