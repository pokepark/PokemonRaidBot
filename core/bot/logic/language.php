<?php

function getTranslationFile($translationTitle) {
  static $savedTranslations = [
    'botLang'       => false,
    'pokemonNames'  => false,
    'pokemonMoves'  => false,
    'pokemonForms'  => false,
    'botHelp'       => false,
    'custom'        => false,
  ];
  static $fileMap = [
    'botLang'       => 'language',
    'pokemonNames'  => 'pokemon',
    'pokemonMoves'  => 'pokemon_moves',
    'pokemonForms'  => 'pokemon_forms',
    'botHelp'       => 'help',
  ];
  $translation = $savedTranslations[$translationTitle];

  // Return translation if it's in memory already
  if($translation !== false) return $translation;

  // Load translations from this file
  $fileContents = file_get_contents(BOT_LANG_PATH . '/' . $fileMap[$translationTitle] . '.json');
  $translation = json_decode($fileContents, true);

  // Has custom translation already been processed?
  if($savedTranslations['custom'] === false) {
    $savedTranslations['custom'] = [];
    // Load custom language file if it exists
    if(is_file(CUSTOM_PATH . '/language.json')) {
      $customContents = file_get_contents(CUSTOM_PATH . '/language.json');
      $savedTranslations['custom'] = json_decode($customContents, true);
    }
  }

  foreach($savedTranslations['custom'] as $title => $value) {
    if(key_exists($title, $translation)) {
      debug_log($title, 'Found custom translation for');
      $translation[$title] = $value;
      unset($savedTranslations['custom'][$title]);
    }
  }
  $savedTranslations[$translationTitle] = $translation;

  return $translation;
}

/**
 * Call the translation function with override parameters.
 * @param string $text
 * @return string translation
 */
function getPublicTranslation($text)
{
  global $config;
  return getTranslation($text, $config->LANGUAGE_PUBLIC);
}

/**
 * Gets a table translation out of the json file.
 * @param string $text
 * @param string $override_language
 * @return string translation
 */
function getTranslation($text, $language = USERLANGUAGE)
{
  debug_log($text,'T:');
  $text = trim($text);

  $tfile = 'botLang';
  // Pokemon name?
  if(strpos($text, 'pokemon_id_') === 0) $tfile = 'pokemonNames';

  // Pokemon form?
  if(strpos($text, 'pokemon_form_') === 0) $tfile = 'pokemonForms';

  // Pokemon moves?
  if(strpos($text, 'pokemon_move_') === 0) $tfile = 'pokemonMoves';

  // Pokemon moves?
  if(strpos($text, 'help_') === 0) $tfile = 'botHelp';

  // Debug log translation file
  debug_log($tfile,'T:');

  $translations = getTranslationFile($tfile);

  // Fallback to English when there is no language key or translation is not yet done.
  if(isset($translations[$text][$language]) && $translations[$text][$language] != 'TRANSLATE')
    $translation = $translations[$text][$language];
  elseif(isset($translations[$text][DEFAULT_LANGUAGE]))
    $translation = $translations[$text][DEFAULT_LANGUAGE];

  // No translation found
  elseif($tfile == 'botHelp')
    $translation = false;
  else
    $translation = $text;
  debug_log($translation,'T:');
  return $translation;
}
