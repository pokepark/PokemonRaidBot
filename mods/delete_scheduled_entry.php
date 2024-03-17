<?php
// Write to log.
debug_log('delete_scheduled_entry()');

// Check access.
$botUser->accessCheck('pokedex');
$id = $data['i'];

if(isset($data['s']) && $data['s'] == 1) {
  my_query('DELETE FROM raid_bosses WHERE id = ?', [$id]);
  include(ROOT_PATH . '/mods/pokedex_list_raids.php');
  exit();
}
$query = my_query('SELECT pokedex_id, pokemon_form_id, date_start, date_end, raid_level FROM raid_bosses WHERE id = ? LIMIT 1', [$id]);
$pokemon = $query->fetch();
$msg = getTranslation('delete_scheduled_confirmation') . CR . CR;
$msg .= $pokemon['date_start'] . ' - ' . $pokemon['date_end'] . ':' . CR;
$msg .= getTranslation($pokemon['raid_level'] . 'stars') . ': ';
$msg .= get_local_pokemon_name($pokemon['pokedex_id'], $pokemon['pokemon_form_id']);

$keys[0][] = button(getTranslation('yes'), ['delete_scheduled_entry', 'i' => $id, 's' => 1]);
$keys[0][] = button(getTranslation('no'), 'pokedex_list_raids');

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
