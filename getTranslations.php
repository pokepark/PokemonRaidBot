<?php
$lang_dir = __DIR__ . '/lang/';
$core_lang_dir = __DIR__ . '/core/lang/';

$lang_directory_url = 'https://raw.githubusercontent.com/PokeMiners/pogo_assets/master/Texts/Latest%20APK/';
$translations_available = ['BrazilianPortuguese','ChineseTraditional','English','French','German','Italian','Japanese','Korean','Russian','Spanish','Thai'];

// Map wanted translations to raidbot language codes
$pokemon_translations_to_fetch = [  
                                    "English"   => ["EN"],
                                    "French"    => ["FR"],
                                    "German"    => ["DE"],
                                    "Russian"   => ["RU"]
                                  ];
$move_translations_to_fetch = [     
                                    "BrazilianPortuguese"   => ["PT-BR"],
                                    "English"               => ["EN", "FI", "NL", "NO", "PL"],
                                    "French"                => ["FR"],
                                    "German"                => ["DE"],
                                    "Italian"               => ["IT"],
                                    "Russian"               => ["RU"]
                              ];

// Initialize array
$move_array = [];
$pokemon_array = [];

// Loop through all available translations
foreach($translations_available as $language) {
    $write_pokemon_translation = array_key_exists($language, $pokemon_translations_to_fetch);
    $write_move_translation = array_key_exists($language, $move_translations_to_fetch);

    // Only read the file if a translation is wanted
    if( $write_pokemon_translation || $write_move_translation ) {
        // Open the file and write it into an array
        $file = curl_open_file($lang_directory_url . $language . '.txt');
        $data = explode("\n", $file);

        // Read the file line by line
        foreach($data as $row) {
            // Handle resource ID rows
            if(substr($row, 0, 1) == 'R') {
                $resource_id = substr(trim($row), 13);
                $resource_part = explode("_",$resource_id);
            }
            // Handle text rows
            if(substr($row, 0, 1) == 'T') {
                $text = substr(trim($row), 6);

                // Filter out mega translations
                if(count($resource_part) == 3 && $resource_part[1] == 'name') {
                    $id = intval($resource_part[2]); // remove leading zeroes
                    // Save pokemon names into an array if pokemon id is larger than 0
                    if($write_pokemon_translation && $resource_part[0] == 'pokemon' && $id > 0) {
                        foreach($pokemon_translations_to_fetch[$language] as $lan) {
                            $pokemon_array['pokemon_id_'.$id][$lan] = $text;
                        }
                    // Save pokemon moves into an array
                    }elseif($write_move_translation && $resource_part[0] == 'move') {
                        foreach($move_translations_to_fetch[$language] as $lan) {
                            $move_array['pokemon_move_'.$id][$lan] = $text;
                        }
                    }
                }
            }
        }
        unset($file);
        unset($data);
    }
}
// Build the path to move translation file
$moves_translation_file = $core_lang_dir . 'moves.json';

// Save translations to the file
file_put_contents($moves_translation_file, json_encode($move_array,JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);

// Build the path to translation file
$pokemon_translation_file = $core_lang_dir . 'pokemon.json';

// Save translations to file
file_put_contents($pokemon_translation_file, json_encode($pokemon_array,JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);

function curl_open_file($input) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $input);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $data = curl_exec($ch);
    curl_close ($ch);
    return $data;
}
?>