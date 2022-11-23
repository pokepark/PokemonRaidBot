<?php
// Write to log.
debug_log('raid_set_poke()');
require_once(LOGIC_PATH . '/alarm.php');
require_once(LOGIC_PATH . '/get_pokemon_by_table_id.php');
require_once(LOGIC_PATH . '/show_raid_poll_small.php');

// For debug.
//debug_log($update);
//debug_log($data);

// Set the id.
$raidId = $data['id'];

// Access check.
$botUser->raidaccessCheck($raidId, 'pokemon');

$pokemon_id_form = get_pokemon_by_table_id($data['arg']);

// Update pokemon in the raid table.
my_query('
  UPDATE  raids
  SET     pokemon = ?,
          pokemon_form = ?
  WHERE   id = ?
  ',[$pokemon_id_form['pokedex_id'], $pokemon_id_form['pokemon_form_id'], $raidId]
);

// Get raid times.
$raid = get_raid($raidId);

// Create the keys.
$keys = [];

// Build message string.
$msg = '';
$msg .= getTranslation('raid_saved') . CR;
$msg .= show_raid_poll_small($raid);

// Build callback message string.
$callback_response = getTranslation('raid_boss_saved');

// Telegram JSON array.
$tg_json = array();

// Answer callback.
$tg_json[] = answerCallbackQuery($update['callback_query']['id'], $callback_response, true);

// Edit message.
$tg_json[] = edit_message($update, $msg, $keys, false, true);

// Update the shared raid polls.
require_once(LOGIC_PATH .'/update_raid_poll.php');
$tg_json = update_raid_poll($raidId, $raid, false, $tg_json, true);

// Alert users.
$tg_json = alarm($raid, $update['callback_query']['from']['id'], 'new_boss', '', $tg_json);

// Telegram multicurl request.
curl_json_multi_request($tg_json);
