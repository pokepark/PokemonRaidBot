<?php
$lang_dir = __DIR__ . '/lang/';

#$lang_directory_url = [
#  'https://raw.githubusercontent.com/PokeMiners/pogo_assets/master/Texts/Latest%20APK/',
#  'https://raw.githubusercontent.com/PokeMiners/pogo_assets/master/Texts/Latest%20Remote/'
#];
$translations_available = ['brazilianportuguese','chinesetraditional','english','french','german','italian','japanese','korean','russian','spanish','thai'];

// Map wanted translations to raidbot language codes
$pokemon_translations_to_fetch = [
  'brazilianportuguese'   => ['PT-BR'],
  'english'   => ['EN'],
  'french'    => ['FR'],
  'german'    => ['DE'],
  'italian'   => ['IT'],
  'russian'   => ['RU'],
  'spanish'   => ['ES'],
];
$move_translations_to_fetch = [
  'brazilianportuguese'   => ['PT-BR'],
  'english'               => ['EN', 'FI', 'NL', 'NO', 'PL'],
  'french'                => ['FR'],
  'german'                => ['DE'],
  'italian'               => ['IT'],
  'russian'               => ['RU'],
  'spanish'               => ['ES'],
];

// Initialize array
$move_array = [];
$pokemon_array = [];

// Loop through all available translations
foreach($translations_available as $language) {
  $write_pokemon_translation = array_key_exists($language, $pokemon_translations_to_fetch);
  $write_move_translation = array_key_exists($language, $move_translations_to_fetch);

  // Only read the file if a translation is wanted
  if( !$write_pokemon_translation && !$write_move_translation ) {
    continue;
  }
  $file = curl_open_file('https://raw.githubusercontent.com/PokeMiners/pogo_assets/master/Texts/Latest%20APK/JSON/i18n_'. $language. '.json');
  $translation = json_decode($file, true);
  $title = true;
  foreach($translation['data'] as $row) {
    // Handle resource ID rows
    if($title) {
      $resource_part = explode('_',$row);
      // Only proceed to processing translation if it's pokemon/move name and it isn't a mega pokemon name
      if(count($resource_part) == 3 && $resource_part[1] == 'name') {
        $title = false;
      }
      continue;
    }
    // Handle text rows
    $id = intval($resource_part[2]); // remove leading zeroes
    // Save pokemon names into an array if pokemon id is larger than 0
    if($write_pokemon_translation && $resource_part[0] == 'pokemon' && $id > 0) {
      foreach($pokemon_translations_to_fetch[$language] as $lan) {
        $pokemon_array['pokemon_id_'.$id][$lan] = $row;
      }
    // Save pokemon moves into an array
    }elseif($write_move_translation && $resource_part[0] == 'move') {
      foreach($move_translations_to_fetch[$language] as $lan) {
        $move_array['pokemon_move_'.$id][$lan] = $row;
      }
    }
    $title = true;
  }
  /*
  // Open the file(s) and write it into an array
  foreach($lang_directory_url as $url) {
    $file = curl_open_file($url . $language . '.txt');
    $data = preg_split('/\r\n|\r|\n/', $file);

    // Read the file line by line
    foreach($data as $row) {
      // Handle resource ID rows
      if(substr($row, 0, 1) == 'R') {
        $resource_id = substr(trim($row), 13);
        $resource_part = explode('_',$resource_id);
      }
      // Handle text rows
      if(substr($row, 0, 1) == 'T') {
        $text = substr(trim($row), 6);

        // Filter out mega translations
        if(count($resource_part) != 3 || $resource_part[1] != 'name') {
          continue;
        }
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
    unset($file);
    unset($data);
  }*/
}
// Bot defaults to using english translations, so no need to add duplicates for every language
$pokemon_array = remove_duplicate_translations($pokemon_array);
$move_array = remove_duplicate_translations($move_array);

// Build the path to move translation file
$moves_translation_file = $lang_dir . 'pokemon_moves.json';

// Save translations to the file
file_put_contents($moves_translation_file, json_encode($move_array,JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);

// Build the path to translation file
$pokemon_translation_file = $lang_dir . 'pokemon.json';

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
function remove_duplicate_translations($array) {
  $new_array = [];
  foreach($array as $translation_id => $translations) {
    foreach($translations as $lang => $translation) {
      if($lang == 'EN' or $translation != $array[$translation_id]['EN'])
        $new_array[$translation_id][$lang] = $translation;
    }
  }
  return $new_array;
}
