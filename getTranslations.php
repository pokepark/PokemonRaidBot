<?php
$lang_dir = __DIR__ . '/lang/';

// Translations available
// pt-br, zh-tw, en, fr, de, it, ja, ko, ru, es, th

// Map wanted translations to raidbot language codes
$translations_to_fetch = [
  'pt-br' => 'PT-BR',
  'en'    => 'EN',
  'fr'    => 'FR',
  'de'    => 'DE',
  'it'    => 'IT',
  'ru'    => 'RU',
  'es'    => 'ES',
];

// Initialize array
$move_array = $pokemon_array = $form_array = [];

// Loop through translations
foreach($translations_to_fetch as $lanfile => $language) {
  $file = curl_open_file('https://raw.githubusercontent.com/WatWowMap/pogo-translations/master/static/locales/'. $lanfile. '.json');
  $translationData = json_decode($file, true);
  foreach($translationData as $title => $translation) {
    $split = explode('_', $title, 2);
    if(count($split) < 2 or intval($split[1]) <= 0) continue;
    [$key, $id] = $split;
    // Save pokemon names into an array if pokemon id is larger than 0
    if($key == 'poke') {
      $pokemon_array['pokemon_id_'.$id][$language] = $translation;
    // Save pokemon moves into an array
    }elseif($key == 'move') {
      $move_array['pokemon_move_'.$id][$language] = $translation;
    }elseif($key == 'form') {
      $form_array['pokemon_form_'. $id][$language] = $translation;
    }
  }
}

// Bot defaults to using english translations, so no need to add duplicates for every language
$pokemon_array = remove_duplicate_translations($pokemon_array);
$move_array = remove_duplicate_translations($move_array);
$form_array = remove_duplicate_translations($form_array);

$pokemon_translation_file = $lang_dir . 'pokemon.json';
$moves_translation_file = $lang_dir . 'pokemon_moves.json';
$forms_translation_file = $lang_dir . 'pokemon_forms.json';

file_put_contents($pokemon_translation_file, json_encode($pokemon_array,JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
file_put_contents($moves_translation_file, json_encode($move_array,JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
file_put_contents($forms_translation_file, json_encode($form_array,JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);

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
      if(
        ($lang == 'EN' or $translation != $array[$translation_id]['EN']) and
        !in_array($array[$translation_id]['EN'], ['Normal','Purified','Shadow'])
        )
        $new_array[$translation_id][$lang] = $translation;
    }
  }
  return $new_array;
}
