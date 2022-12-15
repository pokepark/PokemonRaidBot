<?php
// Write to log.
debug_log('pokedex_set_raid_level()');
require_once(LOGIC_PATH . '/get_pokemon_info.php');

// For debug.
//debug_log($update);
//debug_log($data);

// Check access.
$botUser->accessCheck('pokedex');

// Set the id.
$pokedex_id = $data['p'];

// Get the raid level.
$newLevel = $data['l'] ?? false;

// Split pokedex_id and form
$dex_id_form = explode('-',$pokedex_id,2);
$dex_id = $dex_id_form[0];
$dex_form = $dex_id_form[1];

$pokemon = get_pokemon_info($dex_id, $dex_form);

// Set raid level or show raid levels?
if($newLevel === false) {
  $raid_levels = str_split('0' . RAID_LEVEL_ALL);

  // Init empty keys array.
  $keys = [];

  // Create keys array.
  foreach($raid_levels as $lv) {
    $keys[][] = button(getTranslation($lv . 'stars'), ['pokedex_set_raid_level', 'p' => $pokedex_id, 'l' => $lv]);
  }

  // Back and abort.
  $keys[] = [
    button(getTranslation('back'), ['pokedex_edit_pokemon', 'p' => $pokedex_id]),
    button(getTranslation('abort'), 'exit')
  ];

  // Build callback message string.
  $callback_response = getTranslation('select_raid_level');

  // Set the message.
  $msg = getTranslation('raid_boss') . ': <b>' . get_local_pokemon_name($dex_id, $dex_form) . ' (#' . $dex_id . ')</b>' . CR;
  $msg .= getTranslation('pokedex_current_raid_level') . ' ' . getTranslation($pokemon['raid_level'] . 'stars') . CR . CR;
  $msg .= '<b>' . getTranslation('pokedex_new_raid_level') . ':</b>';
} else {
  // Update raid level of pokemon.
  my_query('
    DELETE FROM raid_bosses
    WHERE   pokedex_id = ?
    AND     pokemon_form_id = ?
    AND     scheduled = 0
    ', [$dex_id, $dex_form]
  );
  if($newLevel != 0) {
    my_query('
      INSERT INTO raid_bosses (pokedex_id, pokemon_form_id, raid_level)
      VALUES (?, ?, ?)
      ',
      [$dex_id, $dex_form, $newLevel]
    );
  }

  // Back to pokemon and done keys.
  $keys[] = [
    button(getTranslation('back') . ' (' . get_local_pokemon_name($dex_id, $dex_form) . ')', ['pokedex_edit_pokemon', 'p' => $pokedex_id]),
    button(getTranslation('done'), ['exit', 'd' => '1'])
  ];

  // Build callback message string.
  $callback_response = getTranslation('pokemon_saved') . ' ' . get_local_pokemon_name($dex_id, $dex_form);

  // Set the message.
  $msg = getTranslation('pokemon_saved') . CR;
  $msg .= '<b>' . get_local_pokemon_name($dex_id, $dex_form) . ' (#' . $dex_id . ')</b>' . CR . CR;
  $msg .= getTranslation('pokedex_new_raid_level') . ':' . CR;
  $msg .= '<b>' . getTranslation($newLevel . 'stars') . '</b>';
}

// Telegram JSON array.
$tg_json = array();

// Answer callback.
$tg_json[] = answerCallbackQuery($update['callback_query']['id'], $callback_response, true);

// Edit message.
$tg_json[] = edit_message($update, $msg, $keys, false, true);

// Telegram multicurl request.
curl_json_multi_request($tg_json);
