<?php
// Write to log.
debug_log('edit_scheduled_entry()');

// Check access.
$botUser->accessCheck('pokedex');
$id = $data['i'];

$query = my_query('SELECT pokedex_id, pokemon_form_id, date_start, date_end, raid_level, disabled FROM raid_bosses WHERE id = ? LIMIT 1', [$id]);
$pokemon = $query->fetch();
if(isset($data['s']) && $data['s'] == 1) {
  my_query('UPDATE raid_bosses SET disabled = NOT disabled WHERE id = ?', [$id]);
  $pokemon['disabled'] = ($pokemon['disabled'] == 1) ? 0 : 1;
}
if(isset($data['s']) && $data['s'] == 2) {
  $msg = getTranslation('delete_scheduled_confirmation') . CR . CR;
  $msg .= $pokemon['date_start'] . ' - ' . $pokemon['date_end'] . ':' . CR;
  $msg .= getTranslation($pokemon['raid_level'] . 'stars') . ': ';
  $msg .= get_local_pokemon_name($pokemon['pokedex_id'], $pokemon['pokemon_form_id']) . CR;
  $msg .= '<b>' . ($pokemon['disabled'] ? getTranslation('disabled') : getTranslation('enabled')) .'</b>';
  $keys[0][0] = button(getTranslation('yes'), ['edit_scheduled_entry', 'i' => $id, 's' => 3]);
  $keys[0][1] = button(getTranslation('no'), ['edit_scheduled_entry', 'i' => $id]);
  // Build callback message string.
  $callback_response = 'OK';

  // Telegram JSON array.
  $tg_json = array();

  // Answer callback.
  $tg_json[] = answerCallbackQuery($update['callback_query']['id'], $callback_response, true);

  // Edit message.
  $tg_json[] = edit_message($update, $msg, $keys, false, true);

  // Telegram multicurl request.
  curl_json_multi_request($tg_json);
  exit();
}
if(isset($data['s']) && $data['s'] == 3) {
  my_query('DELETE FROM raid_bosses WHERE id = ?', [$id]);
  include(ROOT_PATH . '/mods/pokedex_list_raids.php');
  exit();
}
$msg = getTranslation('edit_scheduled_entry') . ':' . CR . CR;
$msg .= EMOJI_CLOCK . SP . $pokemon['date_start'] . ' - ' . $pokemon['date_end'] . CR;
$msg .= getTranslation($pokemon['raid_level'] . 'stars') . ': ';
$msg .= get_local_pokemon_name($pokemon['pokedex_id'], $pokemon['pokemon_form_id']) . CR;
$msg .= '<b>' . ($pokemon['disabled'] ? getTranslation('disabled') : getTranslation('enabled')) .'</b>';

$keys[0][] = button(
  ($pokemon['disabled'] ? getTranslation('enable') : getTranslation('disable')),
  ['edit_scheduled_entry', 'i' => $id, 's' => 1]
);
$keys[1][] = button(getTranslation('delete'), ['edit_scheduled_entry', 'i' => $id, 's' => 2]);
$keys[2][] = button(getTranslation('back'), 'pokedex_list_raids');

// Build callback message string.
$callback_response = 'OK';

// Telegram JSON array.
$tg_json = array();

// Answer callback.
$tg_json[] = answerCallbackQuery($update['callback_query']['id'], $callback_response, true);

// Edit message.
$tg_json[] = edit_message($update, $msg, $keys, false, true);

// Telegram multicurl request.
curl_json_multi_request($tg_json);
